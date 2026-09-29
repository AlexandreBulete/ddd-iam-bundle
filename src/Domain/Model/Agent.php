<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Model;

use AlexandreBulete\DddFoundation\Domain\Model\RecordsEvents;
use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Event\AgentCreated;
use AlexandreBulete\DddIamBundle\Domain\Event\AgentDescribed;
use AlexandreBulete\DddIamBundle\Domain\Event\AgentReactivated;
use AlexandreBulete\DddIamBundle\Domain\Event\AgentRevoked;
use AlexandreBulete\DddIamBundle\Domain\Event\AgentRolesChanged;
use AlexandreBulete\DddIamBundle\Domain\Event\AgentSuspended;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserStatus;

/**
 * A non-human account: an AI agent, an integration (ADR 0011).
 *
 * Governed exactly like a {@see User} — roles, the same permissions, the
 * same lifecycle — but it has no email, no password and never signs in to the
 * back office: it authenticates with {@see ApiToken}s. Two aggregates rather
 * than one with a "kind": a person's invariants (a unique email, a password
 * policy) must not become optional to make room for an account that has none.
 *
 * The status is the account lifecycle shared with users (active, suspended,
 * revoked), hence the shared value object.
 */
final class Agent
{
    use RecordsEvents;

    private function __construct(
        private(set) AgentId $id,
        private(set) string $name,
        private(set) ?string $description,
        private(set) RoleSet $roles,
        private(set) UserStatus $status,
        private(set) \DateTimeImmutable $createdAt,
        private(set) ?\DateTimeImmutable $updatedAt,
        private(set) ?\DateTimeImmutable $revokedAt,
    ) {}

    public static function create(AgentId $id, string $name, ?string $description, RoleSet $roles, \DateTimeImmutable $at): self
    {
        $agent = new self(
            $id,
            self::assertName($name),
            self::normalizeDescription($description),
            $roles,
            UserStatus::fromEnum(UserStatusEnum::ACTIVE),
            $at,
            null,
            null,
        );
        $agent->recordEvent(new AgentCreated((string) $id, $agent->name, $roles->toStrings()));

        return $agent;
    }

    public function describe(string $name, ?string $description, \DateTimeImmutable $at): void
    {
        $this->assertNotRevoked('describe');

        $name = self::assertName($name);
        $description = self::normalizeDescription($description);
        if ($name === $this->name && $description === $this->description) {
            return;
        }

        $this->name = $name;
        $this->description = $description;
        $this->updatedAt = $at;
        $this->recordEvent(new AgentDescribed((string) $this->id, $name, $description));
    }

    public function changeRoles(RoleSet $roles, \DateTimeImmutable $at): void
    {
        $this->assertNotRevoked('change the roles of');

        if ($this->roles->equals($roles)) {
            return;
        }

        $previous = $this->roles;
        $this->roles = $roles;
        $this->updatedAt = $at;
        $this->recordEvent(new AgentRolesChanged((string) $this->id, $roles->toStrings(), $previous->toStrings()));
    }

    public function suspend(\DateTimeImmutable $at): void
    {
        $this->assertNotRevoked('suspend');

        if ($this->status->isSuspended()) {
            return;
        }

        $this->status = UserStatus::fromEnum(UserStatusEnum::SUSPENDED);
        $this->updatedAt = $at;
        $this->recordEvent(new AgentSuspended((string) $this->id));
    }

    public function reactivate(\DateTimeImmutable $at): void
    {
        $this->assertNotRevoked('reactivate');

        if ($this->status->isActive()) {
            return;
        }

        $this->status = UserStatus::fromEnum(UserStatusEnum::ACTIVE);
        $this->updatedAt = $at;
        $this->recordEvent(new AgentReactivated((string) $this->id));
    }

    /**
     * Terminal, like a user's: the row stays, the journal points at it. Its
     * tokens are revoked by the use case, which can see them.
     */
    public function revoke(\DateTimeImmutable $at): void
    {
        if ($this->status->isRevoked()) {
            return;
        }

        $this->status = UserStatus::fromEnum(UserStatusEnum::REVOKED);
        $this->revokedAt = $at;
        $this->updatedAt = $at;
        $this->recordEvent(new AgentRevoked((string) $this->id));
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    private function assertNotRevoked(string $action): void
    {
        if ($this->status->isRevoked()) {
            throw new \DomainException(sprintf('Cannot %s a revoked agent.', $action));
        }
    }

    private static function assertName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 100) {
            throw new \InvalidArgumentException('An agent name is between 1 and 100 characters.');
        }

        return $name;
    }

    private static function normalizeDescription(?string $description): ?string
    {
        $description = $description === null ? null : trim($description);
        if ($description === '') {
            return null;
        }
        if ($description !== null && mb_strlen($description) > 500) {
            throw new \InvalidArgumentException('An agent description is at most 500 characters.');
        }

        return $description;
    }
}
