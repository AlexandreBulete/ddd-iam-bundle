<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Webmozart\Assert\Assert;
use AlexandreBulete\DddIamBundle\Application\Command\RevokeApiToken\RevokeApiTokenCommand;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\ApiTokenResource;
use Symfony\Component\Uid\Ulid;

final readonly class RevokeApiTokenProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): mixed
    {
        Assert::isInstanceOf($data, ApiTokenResource::class);
        Assert::isInstanceOf($data->id, Ulid::class);

        $this->commandBus->dispatch(new RevokeApiTokenCommand(ApiTokenId::fromUlid($data->id)));

        return null;
    }
}
