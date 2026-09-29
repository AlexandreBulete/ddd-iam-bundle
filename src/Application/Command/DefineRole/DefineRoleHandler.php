<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\DefineRole;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\GrantPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\IdentityGeneratorInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PermissionCatalogInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class DefineRoleHandler
{
    public function __construct(
        private RoleDefinitionRepositoryInterface $roles,
        private PermissionCatalogInterface $permissions,
        private GrantPolicyInterface $grantPolicy,
        private IdentityGeneratorInterface $identities,
        private ClockInterface $clock,
    ) {}

    public function __invoke(DefineRoleCommand $command): RoleDefinition
    {
        if ($this->roles->findByRole($command->role) !== null) {
            throw new \DomainException(sprintf('%s is already defined.', $command->role->value()));
        }

        $this->permissions->assertKnown($command->permissions);
        $this->grantPolicy->assertMayGrant($command->permissions);

        $definition = RoleDefinition::define(
            id: $this->identities->nextRoleDefinitionId(),
            role: $command->role,
            label: $command->label,
            permissions: $command->permissions,
            at: $this->clock->now(),
        );
        $this->roles->save($definition);

        return $definition;
    }
}
