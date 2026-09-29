<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Integration;

use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Password;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineRoleDefinitionRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineUserRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\IamPermissionChecker;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\SecurityGrantPolicy;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\UserPermissions;
use AlexandreBulete\DddIamBundle\Tests\Integration\Fixture\NullEventPublisher;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceContext;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceStamp;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Roles to permissions, on a real database: what the bus middleware, the
 * voter and the grant policy all rely on.
 */
final class AccessTest extends TestCase
{
    private EntityManagerInterface $em;
    private DoctrineUserRepository $users;
    private UserPermissions $permissions;
    private IamPermissionChecker $checker;

    protected function setUp(): void
    {
        $this->em = IamDatabase::migrated('iam_');
        $this->users = new DoctrineUserRepository($this->em, new NullEventPublisher());
        $roles = new DoctrineRoleDefinitionRepository($this->em, new NullEventPublisher());

        $roles->save(RoleDefinition::define(
            RoleDefinitionId::generate(),
            Role::fromName('reader'),
            'Lecteur',
            PermissionSet::of(['iam.find_users', 'iam.find_user']),
            new \DateTimeImmutable(),
        ));

        $this->permissions = new UserPermissions($this->users, $roles);
        $this->checker = new IamPermissionChecker($this->permissions);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
    }

    #[Test]
    public function a_user_holds_the_permissions_of_its_roles(): void
    {
        $reader = $this->user('reader@example.com', ['reader']);

        self::assertTrue($this->checker->isGranted($reader, 'iam.find_users'));
        self::assertFalse($this->checker->isGranted($reader, 'iam.revoke_user'));
    }

    #[Test]
    public function super_admin_holds_every_permission(): void
    {
        $root = $this->user('root@example.com', ['super_admin']);

        self::assertTrue($this->checker->isGranted($root, 'iam.revoke_user'));
        self::assertTrue($this->checker->isGranted($root, 'whatever.comes_next'));
    }

    #[Test]
    public function a_suspended_account_holds_nothing(): void
    {
        $actor = $this->user('away@example.com', ['super_admin'], suspended: true);

        self::assertFalse($this->checker->isGranted($actor, 'iam.find_users'));
    }

    #[Test]
    public function an_unknown_actor_holds_nothing(): void
    {
        self::assertFalse($this->checker->isGranted(Actor::user((string) UserId::generate(), 'Ghost'), 'iam.find_users'));
        self::assertFalse($this->checker->isGranted(Actor::user('not-an-id', 'Ghost'), 'iam.find_users'));
    }

    #[Test]
    public function nobody_hands_out_more_than_they_hold(): void
    {
        $reader = $this->user('reader@example.com', ['reader']);
        $policy = $this->policyActingAs($reader, $context);

        $context->within(self::traceOf($reader), static function () use ($policy): void {
            $policy->assertMayGrant(PermissionSet::of(['iam.find_users']));
            $policy->assertMayAssign(new RoleSet(Role::fromName('reader')));
        });

        $this->expectException(\DomainException::class);
        $context->within(self::traceOf($reader), static fn () => $policy->assertMayAssign(new RoleSet(Role::fromName('super_admin'))));
    }

    #[Test]
    public function the_system_is_not_limited(): void
    {
        $policy = $this->policyActingAs(Actor::system(), $context);

        $policy->assertMayAssign(new RoleSet(Role::fromName('super_admin')));
        $this->addToAssertionCount(1);
    }

    /**
     * @param list<string> $roles
     */
    private function user(string $email, array $roles, bool $suspended = false): Actor
    {
        $user = User::create(
            id: UserId::generate(),
            email: Email::fromString($email),
            password: Password::fromString('$2y$13$' . str_repeat('x', 53)),
            roles: RoleSet::fromNames($roles),
            createdAt: new \DateTimeImmutable(),
        );
        if ($suspended) {
            $user->suspend(new \DateTimeImmutable());
        }
        $this->users->save($user);

        return Actor::user((string) $user->id, $email);
    }

    /**
     * @param-out TraceContext $context
     */
    private function policyActingAs(Actor $actor, ?TraceContext &$context): SecurityGrantPolicy
    {
        $context = new TraceContext();

        return new SecurityGrantPolicy(
            $context,
            $this->permissions,
            new DoctrineRoleDefinitionRepository($this->em, new NullEventPublisher()),
        );
    }

    private static function traceOf(Actor $actor): Trace
    {
        return new Trace($actor, new TraceStamp('m', 'm', null, 'test'));
    }
}
