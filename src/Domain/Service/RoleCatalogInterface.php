<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

use AlexandreBulete\DddIamBundle\Domain\Exception\UnknownRoleException;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;

/**
 * Domain port — the roles that exist: those defined in the back office, plus
 * the system role super_admin (ADR 0008). Everything downstream — the user
 * form choices, the grid filter — reads from here.
 */
interface RoleCatalogInterface
{
    /**
     * Every defined role, super_admin included.
     */
    public function all(): RoleSet;

    public function has(Role $role): bool;

    /**
     * @throws UnknownRoleException if any role is not declared
     */
    public function assertKnown(RoleSet $roles): void;
}
