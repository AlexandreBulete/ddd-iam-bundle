<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\RevokeUser\RevokeUserCommand;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\UserResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Symfony\Component\Uid\Ulid;
use Webmozart\Assert\Assert;

/**
 * The back office's "delete" button revokes; it does not delete.
 *
 * Erasing the row would leave every audit entry pointing at an account that no
 * longer exists — which is precisely the history an audit log is kept for. A
 * genuine erasure (GDPR right to be forgotten) is a different use case, one
 * that scrubs personal data while keeping the identifier.
 *
 * Handles a single resource and a bulk selection alike, since Sylius routes
 * both Delete and BulkDelete here.
 */
final readonly class DeleteUserProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): mixed
    {
        foreach (is_array($data) ? $data : [$data] as $resource) {
            Assert::isInstanceOf($resource, UserResource::class);
            Assert::isInstanceOf($resource->id, Ulid::class);

            $this->commandBus->dispatch(new RevokeUserCommand(UserId::fromUlid($resource->id)));
        }

        return null;
    }
}
