<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Model;

use AlexandreBulete\DddFoundation\Domain\Model\RecordsEvents;
use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Event\UserCreated;
use AlexandreBulete\DddIamBundle\Domain\Event\UserEmailChanged;
use AlexandreBulete\DddIamBundle\Domain\Event\UserPasswordChanged;
use AlexandreBulete\DddIamBundle\Domain\Event\UserReactivated;
use AlexandreBulete\DddIamBundle\Domain\Event\UserRenamed;
use AlexandreBulete\DddIamBundle\Domain\Event\UserRevoked;
use AlexandreBulete\DddIamBundle\Domain\Event\UserRolesChanged;
use AlexandreBulete\DddIamBundle\Domain\Event\UserSuspended;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Password;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserStatus;

/**
 * The IAM aggregate: who may authenticate, with which credentials, and what
 * they are authorised to do.
 *
 * SCOPE — read this before adding a field. This aggregate holds identity,
 * credentials, lifecycle and roles. It does NOT hold profile or business data:
 * a phone number, an avatar, a store assignment, a signature block are not
 * IAM concerns. They belong to a companion aggregate in the project's own
 * Bounded Context, keyed by this {@see UserId} with no cross-context foreign
 * key. Keeping that line is what lets this bundle be shared across projects
 * that have nothing else in common.
 *
 * `firstName` / `lastName` are the deliberate exception: every back-office
 * user list needs a human label, and duplicating a name table in every project
 * for that alone would be worse.
 *
 * EXTENSION — properties are `protected(set)` and the constructor is
 * `protected` so a project can subclass through `iam.user_class` when it truly
 * must. That is the escape hatch, not the normal path; prefer the companion
 * aggregate above.
 *
 * A subclass MUST keep the constructor signature — `create()` calls
 * `new static()`. Extra state belongs in the subclass's own named
 * constructor, not in extra arguments here.
 *
 * @phpstan-consistent-constructor
 */
class User
{
    use RecordsEvents;

    protected function __construct(
        protected(set) UserId $id,
        protected(set) Email $email,
        protected(set) Password $password,
        protected(set) ?string $firstName,
        protected(set) ?string $lastName,
        protected(set) RoleSet $roles,
        protected(set) UserStatus $status,
        protected(set) \DateTimeImmutable $createdAt,
        protected(set) ?\DateTimeImmutable $updatedAt,
        protected(set) ?\DateTimeImmutable $revokedAt,
    ) {}

    public static function create(
        Email $email,
        Password $password,
        RoleSet $roles,
        ?string $firstName = null,
        ?string $lastName = null,
    ): static {
        $user = new static(
            id: UserId::generate(),
            email: $email,
            password: $password,
            firstName: $firstName,
            lastName: $lastName,
            roles: $roles,
            status: UserStatus::fromEnum(UserStatusEnum::ACTIVE),
            createdAt: new \DateTimeImmutable(),
            updatedAt: null,
            revokedAt: null,
        );

        $user->recordEvent(new UserCreated(
            userId: (string) $user->id,
            email: $email->value(),
            firstName: $firstName,
            lastName: $lastName,
            roles: $roles->toStrings(),
        ));

        return $user;
    }

    public function rename(?string $firstName, ?string $lastName): void
    {
        $this->assertNotRevoked('rename');

        if ($firstName === $this->firstName && $lastName === $this->lastName) {
            return; // idempotent
        }

        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->touch();
        $this->recordEvent(new UserRenamed((string) $this->id, $firstName, $lastName));
    }

    public function changeEmail(Email $email): void
    {
        $this->assertNotRevoked('change the email of');

        if ($this->email->equals($email)) {
            return; // idempotent
        }

        $this->email = $email;
        $this->touch();
        $this->recordEvent(new UserEmailChanged((string) $this->id, $email->value()));
    }

    public function changePassword(Password $password): void
    {
        $this->assertNotRevoked('change the password of');

        $this->password = $password;
        $this->touch();
        $this->recordEvent(new UserPasswordChanged((string) $this->id));
    }

    /**
     * Replaces the whole role set — never a partial grant.
     *
     * Authorisation changes are reviewed as a whole ("this user is now
     * moderator and nothing else"), and a set-based operation is naturally
     * idempotent, which a grant/revoke pair is not.
     *
     * Whether each role actually exists is checked one layer up, by the
     * handler against {@see \AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface}:
     * the catalogue is deployment configuration, not an aggregate invariant.
     */
    public function changeRoles(RoleSet $roles): void
    {
        $this->assertNotRevoked('change the roles of');

        if ($this->roles->equals($roles)) {
            return; // idempotent
        }

        $previous = $this->roles;
        $this->roles = $roles;
        $this->touch();
        $this->recordEvent(new UserRolesChanged(
            userId: (string) $this->id,
            roles: $roles->toStrings(),
            previousRoles: $previous->toStrings(),
        ));
    }

    public function suspend(): void
    {
        $this->assertNotRevoked('suspend');

        if ($this->status->isSuspended()) {
            return; // idempotent
        }

        $this->status = UserStatus::fromEnum(UserStatusEnum::SUSPENDED);
        $this->touch();
        $this->recordEvent(new UserSuspended((string) $this->id));
    }

    public function reactivate(): void
    {
        $this->assertNotRevoked('reactivate');

        if ($this->status->isActive()) {
            return; // idempotent
        }

        $this->status = UserStatus::fromEnum(UserStatusEnum::ACTIVE);
        $this->touch();
        $this->recordEvent(new UserReactivated((string) $this->id));
    }

    /**
     * Terminal state — a revoked account is never reopened.
     *
     * The row survives on purpose: the audit trail references this user, and
     * deleting it would leave a history pointing at nothing. A GDPR erasure
     * request is a different use case (scrubbing personal data in place), not
     * this one.
     */
    public function revoke(): void
    {
        if ($this->status->isRevoked()) {
            return; // idempotent
        }

        $this->status = UserStatus::fromEnum(UserStatusEnum::REVOKED);
        $this->revokedAt = new \DateTimeImmutable();
        $this->touch();
        $this->recordEvent(new UserRevoked((string) $this->id));
    }

    public function getFullName(): ?string
    {
        if ($this->firstName === null && $this->lastName === null) {
            return null;
        }

        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    protected function assertNotRevoked(string $action): void
    {
        if ($this->status->isRevoked()) {
            throw new \DomainException(sprintf('Cannot %s a revoked user', $action));
        }
    }

    protected function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
