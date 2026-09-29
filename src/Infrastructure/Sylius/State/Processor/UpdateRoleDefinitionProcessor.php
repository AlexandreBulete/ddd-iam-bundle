<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeRolePermissions\ChangeRolePermissionsCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RelabelRole\RelabelRoleCommand;
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
 * One command per intention, the aggregate deciding what actually changed —
 * like UpdateUserProcessor. The system role has no permissions field.
 */
final readonly class UpdateRoleDefinitionProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): RoleDefinitionResource
    {
        Assert::isInstanceOf($data, RoleDefinitionResource::class);
        Assert::isInstanceOf($data->id, Ulid::class);
        Assert::stringNotEmpty($data->label);

        $id = RoleDefinitionId::fromUlid($data->id);

        /** @var RoleDefinition $definition */
        $definition = $this->commandBus->dispatch(new RelabelRoleCommand($id, $data->label));

        if (!$definition->system) {
            /** @var RoleDefinition $definition */
            $definition = $this->commandBus->dispatch(new ChangeRolePermissionsCommand($id, PermissionSet::of($data->permissions)));
        }

        return RoleDefinitionResource::fromModel($definition);
    }
}
