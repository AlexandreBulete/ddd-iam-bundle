<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\DefineRole\DefineRoleCommand;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\RoleDefinitionResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Webmozart\Assert\Assert;

final readonly class CreateRoleDefinitionProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): RoleDefinitionResource
    {
        Assert::isInstanceOf($data, RoleDefinitionResource::class);
        Assert::stringNotEmpty($data->name);
        Assert::stringNotEmpty($data->label);

        return RoleDefinitionResource::fromModel($this->commandBus->dispatch(new DefineRoleCommand(
            role: Role::fromName($data->name),
            label: $data->label,
            permissions: PermissionSet::of($data->permissions),
        )));
    }
}
