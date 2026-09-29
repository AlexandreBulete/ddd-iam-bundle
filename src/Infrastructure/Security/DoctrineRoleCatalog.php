<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Exception\UnknownRoleException;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;

/**
 * The roles that exist: the role definitions (ADR 0008).
 */
final readonly class DoctrineRoleCatalog implements RoleCatalogInterface
{
    public function __construct(
        private RoleDefinitionRepositoryInterface $definitions,
    ) {}

    public function all(): RoleSet
    {
        return new RoleSet(...array_map(
            static fn (RoleDefinition $definition): Role => $definition->role,
            $this->definitions->all(),
        ));
    }

    public function has(Role $role): bool
    {
        return $this->definitions->findByRole($role) !== null;
    }

    public function assertKnown(RoleSet $roles): void
    {
        $known = $this->all();
        foreach ($roles as $role) {
            if (!$known->contains($role)) {
                throw new UnknownRoleException($role, $known->toStrings());
            }
        }
    }
}
