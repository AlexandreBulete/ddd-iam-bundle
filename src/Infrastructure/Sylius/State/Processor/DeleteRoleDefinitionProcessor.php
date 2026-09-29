<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\RemoveRole\RemoveRoleCommand;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\RoleDefinitionResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Symfony\Component\Uid\Ulid;
use Webmozart\Assert\Assert;

final readonly class DeleteRoleDefinitionProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): mixed
    {
        Assert::isInstanceOf($data, RoleDefinitionResource::class);
        Assert::isInstanceOf($data->id, Ulid::class);

        $this->commandBus->dispatch(new RemoveRoleCommand(RoleDefinitionId::fromUlid($data->id)));

        return null;
    }
}
