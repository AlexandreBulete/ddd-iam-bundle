<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Unit\Domain;

use AlexandreBulete\DddIamBundle\Domain\Event\AgentCreated;
use AlexandreBulete\DddIamBundle\Domain\Event\AgentDescribed;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AgentTest extends TestCase
{
    #[Test]
    public function an_agent_starts_active_with_the_roles_it_is_given(): void
    {
        $agent = self::agent();

        self::assertTrue($agent->isActive());
        self::assertTrue($agent->roles->equals(RoleSet::fromNames(['reader'])));
        self::assertInstanceOf(AgentCreated::class, $agent->releaseEvents()[0]);
    }

    #[Test]
    public function describing_it_the_same_way_records_nothing(): void
    {
        $agent = self::agent();
        $agent->releaseEvents();

        $agent->describe(' Veille ', '', new \DateTimeImmutable());
        self::assertSame([], $agent->releaseEvents(), 'trimmed name, blank description: nothing changed');

        $agent->describe('Veille sécurité', 'Lit les bulletins', new \DateTimeImmutable());
        self::assertInstanceOf(AgentDescribed::class, $agent->releaseEvents()[0]);
        self::assertSame('Lit les bulletins', $agent->description);
    }

    #[Test]
    public function a_suspended_agent_comes_back_a_revoked_one_does_not(): void
    {
        $agent = self::agent();

        $agent->suspend(new \DateTimeImmutable());
        self::assertFalse($agent->isActive());
        $agent->reactivate(new \DateTimeImmutable());
        self::assertTrue($agent->isActive());

        $agent->revoke(new \DateTimeImmutable());
        $this->expectException(\DomainException::class);
        $agent->reactivate(new \DateTimeImmutable());
    }

    #[Test]
    public function an_agent_has_a_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Agent::create(AgentId::generate(), '  ', null, RoleSet::empty(), new \DateTimeImmutable());
    }

    private static function agent(): Agent
    {
        return Agent::create(AgentId::generate(), 'Veille', null, RoleSet::fromNames(['reader']), new \DateTimeImmutable());
    }
}
