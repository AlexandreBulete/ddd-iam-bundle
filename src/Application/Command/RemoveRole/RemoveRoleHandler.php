<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\RemoveRole;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Domain\Exception\EntityNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Exception\RoleStillAssignedException;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;

#[AsCommandHandler]
final readonly class RemoveRoleHandler
{
    public function __construct(
        private RoleDefinitionRepositoryInterface $roles,
        private UserRepositoryInterface $users,
    ) {}

    public function __invoke(RemoveRoleCommand $command): void
    {
        $definition = $this->roles->findById($command->id)
            ?? throw new EntityNotFoundException(RoleDefinition::class, $command->id);

        $carriers = $this->users->countWithRole($definition->role);
        if ($carriers > 0) {
            throw new RoleStillAssignedException($definition->role, $carriers);
        }

        $definition->remove();
        $this->roles->remove($definition);
    }
}
