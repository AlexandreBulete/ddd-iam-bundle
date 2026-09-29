<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;

/**
 * What one account may do: every permission if it carries super_admin, the
 * union of its roles' permissions otherwise.
 */
final readonly class UserAccess
{
    public function __construct(
        public bool $superAdmin,
        public PermissionSet $permissions,
    ) {}

    public function grants(string $permission): bool
    {
        return $this->superAdmin || $this->permissions->contains($permission);
    }

    public function grantsAll(PermissionSet $permissions): bool
    {
        return $this->superAdmin || $this->permissions->containsAll($permissions);
    }
}
