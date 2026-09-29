<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;

/**
 * Domain port — no escalation of privileges (ADR 0008): whoever is acting may
 * only hand out what they hold themselves.
 *
 * The adapter knows who is acting; the Domain only states the rule.
 */
interface GrantPolicyInterface
{
    /**
     * @throws \DomainException when a permission is not held by whoever acts
     */
    public function assertMayGrant(PermissionSet $permissions): void;

    /**
     * Assigning a role hands out everything it grants: super_admin only by a
     * super_admin, any other role only by someone holding all its permissions.
     *
     * @throws \DomainException
     */
    public function assertMayAssign(RoleSet $roles): void;
}
