<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Exception\UnknownRoleException;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;

/**
 * The `iam.roles` configuration, as a Domain service.
 *
 * This is the class that makes "add a role" a five-line YAML change instead of
 * a fork. It is built once at compile time from the bundle's defaults merged
 * with the project's additions, so a typo in a role name fails at the point of
 * the grant ({@see assertKnown()}) rather than silently denying access months
 * later.
 */
final readonly class RoleCatalog implements RoleCatalogInterface
{
    private RoleSet $roles;
    private RoleSet $defaults;

    /**
     * @param list<string> $roleNames    every declared role, as configuration names
     * @param list<string> $defaultNames roles granted to a user created without an explicit set
     */
    public function __construct(array $roleNames, array $defaultNames)
    {
        $this->roles = RoleSet::fromNames($roleNames);
        $this->defaults = RoleSet::fromNames($defaultNames);
    }

    public function all(): RoleSet
    {
        return $this->roles;
    }

    public function has(Role $role): bool
    {
        return $this->roles->contains($role);
    }

    public function defaults(): RoleSet
    {
        return $this->defaults;
    }

    public function assertKnown(RoleSet $roles): void
    {
        foreach ($roles as $role) {
            if (!$this->has($role)) {
                throw new UnknownRoleException($role, $this->roles->toStrings());
            }
        }
    }
}
