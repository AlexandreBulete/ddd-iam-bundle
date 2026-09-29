<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Repository;

use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;

/**
 * @extends RepositoryInterface<RoleDefinition>
 */
interface RoleDefinitionRepositoryInterface extends RepositoryInterface
{
    public function save(RoleDefinition $definition): void;

    public function remove(RoleDefinition $definition): void;

    public function findByRole(Role $role): ?RoleDefinition;

    /**
     * @return list<RoleDefinition>
     */
    public function all(): array;
}
