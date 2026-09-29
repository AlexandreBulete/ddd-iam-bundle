<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\RelabelRole;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Domain\Exception\EntityNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class RelabelRoleHandler
{
    public function __construct(
        private RoleDefinitionRepositoryInterface $roles,
        private ClockInterface $clock,
    ) {}

    public function __invoke(RelabelRoleCommand $command): RoleDefinition
    {
        $definition = $this->roles->findById($command->id)
            ?? throw new EntityNotFoundException(RoleDefinition::class, $command->id);

        $definition->relabel($command->label, $this->clock->now());
        $this->roles->save($definition);

        return $definition;
    }
}
