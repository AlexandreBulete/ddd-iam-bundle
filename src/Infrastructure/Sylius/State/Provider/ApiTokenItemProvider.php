<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider;

use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\ApiTokenResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Context\Option\RequestOption;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProviderInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Only revocation reads a single token, and it needs nothing but its id: the
 * use case checks that it exists. No read permission is spent on it.
 */
final readonly class ApiTokenItemProvider implements ProviderInterface
{
    public function provide(Operation $operation, Context $context): ?ApiTokenResource
    {
        $id = $context->get(RequestOption::class)?->request()->attributes->getString('id');
        if ($id === null || !Ulid::isValid($id)) {
            return null;
        }

        return new ApiTokenResource(id: Ulid::fromString($id));
    }
}
