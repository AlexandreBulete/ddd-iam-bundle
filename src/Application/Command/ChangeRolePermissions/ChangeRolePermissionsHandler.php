<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ChangeRolePermissions;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Domain\Exception\EntityNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\GrantPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PermissionCatalogInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class ChangeRolePermissionsHandler
{
    public function __construct(
        private RoleDefinitionRepositoryInterface $roles,
        private PermissionCatalogInterface $permissions,
        private GrantPolicyInterface $grantPolicy,
        private ClockInterface $clock,
    ) {}

    public function __invoke(ChangeRolePermissionsCommand $command): RoleDefinition
    {
        $definition = $this->roles->findById($command->id)
            ?? throw new EntityNotFoundException(RoleDefinition::class, $command->id);

        $this->permissions->assertKnown($command->permissions);
        // What is added, and what is taken away: both change what the role's
        // users can do, so both must be held by whoever acts.
        $this->grantPolicy->assertMayGrant($command->permissions->merge($definition->permissions));

        $definition->changePermissions($command->permissions, $this->clock->now());
        $this->roles->save($definition);

        return $definition;
    }
}
