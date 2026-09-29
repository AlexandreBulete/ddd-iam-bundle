<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Domain\Exception\EntityNotFoundException;
use AlexandreBulete\DddIamBundle\Application\Command\IssueApiToken\IssueApiTokenCommand;
use AlexandreBulete\DddIamBundle\Application\Command\IssueApiToken\IssueApiTokenHandler;
use AlexandreBulete\DddIamBundle\Application\Command\IssueApiToken\IssuedApiToken;
use AlexandreBulete\DddIamBundle\Application\Command\RemoveRole\RemoveRoleCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RemoveRole\RemoveRoleHandler;
use AlexandreBulete\DddIamBundle\Application\Command\RevokeAgent\RevokeAgentCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RevokeAgent\RevokeAgentHandler;
use AlexandreBulete\DddIamBundle\Domain\Exception\RoleStillAssignedException;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineAgentRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineApiTokenRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineRoleDefinitionRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineUserRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Identity\UlidIdentityGenerator;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\AgentSecurityUser;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\ApiTokenHandler;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\RandomSecretGenerator;
use AlexandreBulete\DddIamBundle\Tests\Integration\Fixture\NullEventPublisher;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorKind;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * ADR 0011 end to end, on a real database: a token is issued, presented,
 * refused whenever it should be — and the actor it yields names it.
 */
final class ApiTokenAuthenticationTest extends TestCase
{
    private EntityManagerInterface $em;
    private DoctrineAgentRepository $agents;
    private DoctrineApiTokenRepository $tokens;
    private MockClock $clock;
    private ApiTokenHandler $handler;

    protected function setUp(): void
    {
        $this->em = IamDatabase::migrated('iam_');
        $this->agents = new DoctrineAgentRepository($this->em, new NullEventPublisher());
        $this->tokens = new DoctrineApiTokenRepository($this->em, new NullEventPublisher());
        $this->clock = new MockClock(new \DateTimeImmutable('2026-01-01 10:00')); // the application's timezone, as the database reads it back
        $this->handler = new ApiTokenHandler($this->tokens, $this->agents, $this->clock, 'pilot');
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
    }

    #[Test]
    public function an_issued_token_authenticates_its_agent_and_is_named_in_the_actor(): void
    {
        $agent = $this->agent();
        $issued = $this->issue($agent);

        $user = $this->authenticate($issued->plain->toString());

        self::assertInstanceOf(AgentSecurityUser::class, $user);
        $actor = $user->toActor();
        self::assertSame(ActorKind::Agent, $actor->kind);
        self::assertSame((string) $agent->id, $actor->id);
        self::assertSame('Veille', $actor->label);
        self::assertSame((string) $issued->token->id, $actor->credential);
        self::assertStringStartsWith('pilot_', $issued->plain->toString());
    }

    #[Test]
    public function only_the_digest_is_stored(): void
    {
        $issued = $this->issue($this->agent());

        $row = $this->em->getConnection()->fetchAssociative('SELECT * FROM iam_api_token');

        self::assertIsArray($row);
        self::assertSame(hash('sha256', $issued->plain->secret), $row['digest']);
        self::assertStringNotContainsString($issued->plain->secret, (string) json_encode($row));
    }

    #[Test]
    public function using_a_token_is_remembered(): void
    {
        $issued = $this->issue($this->agent());

        $this->authenticate($issued->plain->toString());

        $this->em->clear();
        self::assertEquals($this->clock->now(), $this->tokens->findById($issued->token->id)?->lastUsedAt);
    }

    #[Test]
    public function a_wrong_secret_is_refused(): void
    {
        $issued = $this->issue($this->agent());

        $this->expectException(BadCredentialsException::class);
        $this->authenticate('pilot_' . (string) $issued->token->id . '_' . str_repeat('x', 43));
    }

    #[Test]
    public function an_expired_token_is_refused(): void
    {
        $issued = $this->issue($this->agent());
        $this->clock->modify('+91 days');

        $this->expectException(BadCredentialsException::class);
        $this->authenticate($issued->plain->toString());
    }

    #[Test]
    public function a_token_of_a_suspended_agent_is_refused(): void
    {
        $agent = $this->agent();
        $issued = $this->issue($agent);
        $agent->suspend($this->clock->now());
        $this->agents->save($agent);

        $this->expectException(BadCredentialsException::class);
        $this->authenticate($issued->plain->toString());
    }

    #[Test]
    public function revoking_an_agent_revokes_its_tokens(): void
    {
        $agent = $this->agent();
        $first = $this->issue($agent);
        $second = $this->issue($agent);

        (new RevokeAgentHandler($this->agents, $this->tokens, $this->clock))(new RevokeAgentCommand($agent->id));

        $this->em->clear();
        foreach ([$first, $second] as $issued) {
            self::assertTrue($this->tokens->findById($issued->token->id)?->isRevoked());
        }
    }

    #[Test]
    public function a_role_an_agent_carries_cannot_be_removed(): void
    {
        $roles = new DoctrineRoleDefinitionRepository($this->em, new NullEventPublisher());
        $reader = RoleDefinition::define(RoleDefinitionId::generate(), Role::fromName('reader'), 'Lecteur', PermissionSet::none(), $this->clock->now());
        $roles->save($reader);
        $this->agent(['reader']);

        $this->expectException(RoleStillAssignedException::class);
        (new RemoveRoleHandler($roles, new DoctrineUserRepository($this->em, new NullEventPublisher()), $this->agents))(new RemoveRoleCommand($reader->id));
    }

    #[Test]
    public function tokens_are_issued_to_existing_agents_only(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->issueHandler()(new IssueApiTokenCommand(AgentId::generate(), 'CI', $this->clock->now()->modify('+1 day')));
    }

    /**
     * @param list<string> $roles
     */
    private function agent(array $roles = []): Agent
    {
        $agent = Agent::create(AgentId::generate(), 'Veille', null, RoleSet::fromNames($roles), $this->clock->now());
        $this->agents->save($agent);

        return $agent;
    }

    private function issue(Agent $agent): IssuedApiToken
    {
        return $this->issueHandler()(new IssueApiTokenCommand($agent->id, 'CI lapsa', $this->clock->now()->modify('+90 days')));
    }

    private function issueHandler(): IssueApiTokenHandler
    {
        return new IssueApiTokenHandler($this->agents, $this->tokens, new UlidIdentityGenerator(), new RandomSecretGenerator(), $this->clock, 'pilot');
    }

    private function authenticate(string $presented): object
    {
        $badge = $this->handler->getUserBadgeFrom($presented);

        return $badge->getUser();
    }
}
