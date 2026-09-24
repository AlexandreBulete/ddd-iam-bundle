<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

use AlexandreBulete\DddIamBundle\Domain\Exception\UnknownRoleException;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;

/**
 * Domain port — the set of roles this deployment recognises.
 *
 * This is the seam that makes the bundle reusable without a fork: the bundle
 * ships `user`, `admin` and `super_admin`, and a project adds its own under
 * `iam.roles`. Everything downstream — the admin form choices, the grid
 * filter, `security.role_hierarchy` — reads from here rather than from a
 * hardcoded list.
 */
interface RoleCatalogInterface
{
    /**
     * Every declared role, bundle defaults and project additions alike.
     */
    public function all(): RoleSet;

    public function has(Role $role): bool;

    /**
     * The roles granted to a user created without an explicit set
     * (`iam.default_roles`).
     */
    public function defaults(): RoleSet;

    /**
     * @throws UnknownRoleException if any role is not declared
     */
    public function assertKnown(RoleSet $roles): void;
}
