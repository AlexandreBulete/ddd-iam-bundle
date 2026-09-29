<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Webmozart\Assert\Assert;
use AlexandreBulete\DddIamBundle\Application\Command\CreateAgent\CreateAgentCommand;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\AgentResource;

final readonly class CreateAgentProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): AgentResource
    {
        Assert::isInstanceOf($data, AgentResource::class);
        Assert::stringNotEmpty($data->name);

        return AgentResource::fromModel($this->commandBus->dispatch(new CreateAgentCommand(
            name: $data->name,
            description: $data->description,
            roles: RoleSet::fromNames($data->roles),
        )));
    }
}
