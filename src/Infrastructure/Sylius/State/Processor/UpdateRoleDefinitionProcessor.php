<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeRolePermissions\ChangeRolePermissionsCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RelabelRole\RelabelRoleCommand;
use AlexandreBulete\DddIamBundle\Application\Query\FindRoleDefinition\FindRoleDefinitionQuery;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\RoleDefinitionResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Symfony\Component\Uid\Ulid;
use Webmozart\Assert\Assert;

/**
 * A form submits every field; only what the user changed becomes a command —
 * like UpdateUserProcessor. An untouched field must not be journaled as an
 * action, nor require a permission the user did not use. The system role has
 * no permissions field.
 */
final readonly class UpdateRoleDefinitionProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): RoleDefinitionResource
    {
        Assert::isInstanceOf($data, RoleDefinitionResource::class);
        Assert::isInstanceOf($data->id, Ulid::class);
        Assert::stringNotEmpty($data->label);

        $id = RoleDefinitionId::fromUlid($data->id);

        /** @var RoleDefinition $definition */
        $definition = $this->queryBus->ask(new FindRoleDefinitionQuery($id));

        if ($data->label !== $definition->label) {
            /** @var RoleDefinition $definition */
            $definition = $this->commandBus->dispatch(new RelabelRoleCommand($id, $data->label));
        }

        $permissions = PermissionSet::of($data->permissions);
        if (!$definition->system && !$permissions->equals($definition->permissions)) {
            /** @var RoleDefinition $definition */
            $definition = $this->commandBus->dispatch(new ChangeRolePermissionsCommand($id, $permissions));
        }

        return RoleDefinitionResource::fromModel($definition);
    }
}
