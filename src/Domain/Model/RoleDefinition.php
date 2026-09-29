<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Model;

use AlexandreBulete\DddFoundation\Domain\Model\RecordsEvents;
use AlexandreBulete\DddIamBundle\Domain\Event\RoleDefined;
use AlexandreBulete\DddIamBundle\Domain\Event\RoleDefinitionRelabeled;
use AlexandreBulete\DddIamBundle\Domain\Event\RoleDefinitionRemoved;
use AlexandreBulete\DddIamBundle\Domain\Event\RolePermissionsChanged;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;

/**
 * What a role grants: the permissions behind a {@see Role} that users carry
 * (ADR 0008). Roles are data — defined and changed in the back office — not
 * configuration.
 *
 * One role is a system role: `super_admin`, created by the bundle's
 * migration. It grants every permission, present and future; it can be
 * relabeled, never restricted nor removed — a wrong click must not lock
 * everyone out of the back office.
 */
final class RoleDefinition
{
    use RecordsEvents;

    public const SUPER_ADMIN = 'super_admin';

    private function __construct(
        private(set) RoleDefinitionId $id,
        private(set) Role $role,
        private(set) string $label,
        private(set) PermissionSet $permissions,
        private(set) bool $system,
        private(set) \DateTimeImmutable $createdAt,
        private(set) ?\DateTimeImmutable $updatedAt,
    ) {}

    public static function define(
        RoleDefinitionId $id,
        Role $role,
        string $label,
        PermissionSet $permissions,
        \DateTimeImmutable $at,
    ): self {
        if ($role->equals(self::superAdminRole())) {
            throw new \DomainException('super_admin is a system role: it exists already.');
        }

        $definition = new self($id, $role, self::assertLabel($label), $permissions, false, $at, null);
        $definition->recordEvent(new RoleDefined((string) $id, $role->value(), $permissions->toArray()));

        return $definition;
    }

    /**
     * The super_admin role, as the bundle's migration creates it.
     */
    public static function superAdmin(RoleDefinitionId $id, string $label, \DateTimeImmutable $at): self
    {
        return new self($id, self::superAdminRole(), self::assertLabel($label), PermissionSet::none(), true, $at, null);
    }

    public static function superAdminRole(): Role
    {
        return Role::fromName(self::SUPER_ADMIN);
    }

    public function grants(string $permission): bool
    {
        return $this->system || $this->permissions->contains($permission);
    }

    /**
     * Every permission this role grants, among those that exist — the system
     * role grants them all.
     */
    public function grantedAmong(PermissionSet $existing): PermissionSet
    {
        return $this->system ? $existing : PermissionSet::of(array_filter(
            $existing->toArray(),
            fn (string $permission): bool => $this->permissions->contains($permission),
        ));
    }

    /**
     * Replaces the whole set — reviewed as a whole, idempotent.
     */
    public function changePermissions(PermissionSet $permissions, \DateTimeImmutable $at): void
    {
        if ($this->system) {
            throw new \DomainException('super_admin grants every permission: it cannot be restricted.');
        }

        if ($this->permissions->equals($permissions)) {
            return;
        }

        $previous = $this->permissions;
        $this->permissions = $permissions;
        $this->updatedAt = $at;
        $this->recordEvent(new RolePermissionsChanged((string) $this->id, $this->role->value(), $permissions->toArray(), $previous->toArray()));
    }

    public function relabel(string $label, \DateTimeImmutable $at): void
    {
        $label = self::assertLabel($label);
        if ($label === $this->label) {
            return;
        }

        $this->label = $label;
        $this->updatedAt = $at;
        $this->recordEvent(new RoleDefinitionRelabeled((string) $this->id, $label));
    }

    /**
     * Records the removal; the repository deletes. Whether users still carry
     * the role is checked by the use case, which can see them.
     */
    public function remove(): void
    {
        if ($this->system) {
            throw new \DomainException('super_admin is a system role: it cannot be removed.');
        }

        $this->recordEvent(new RoleDefinitionRemoved((string) $this->id, $this->role->value()));
    }

    private static function assertLabel(string $label): string
    {
        $label = trim($label);
        if ($label === '' || mb_strlen($label) > 100) {
            throw new \InvalidArgumentException('A role label is between 1 and 100 characters.');
        }

        return $label;
    }
}
