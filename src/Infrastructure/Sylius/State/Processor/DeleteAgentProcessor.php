<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Webmozart\Assert\Assert;
use AlexandreBulete\DddIamBundle\Application\Command\RevokeAgent\RevokeAgentCommand;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\AgentResource;
use Symfony\Component\Uid\Ulid;

/**
 * Delete revokes, like for a user: the row stays, the journal points at it,
 * and every token of the agent stops working.
 */
final readonly class DeleteAgentProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): mixed
    {
        Assert::isInstanceOf($data, AgentResource::class);
        Assert::isInstanceOf($data->id, Ulid::class);

        $this->commandBus->dispatch(new RevokeAgentCommand(AgentId::fromUlid($data->id)));

        return null;
    }
}
