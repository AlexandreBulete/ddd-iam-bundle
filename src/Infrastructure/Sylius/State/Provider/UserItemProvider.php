<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider;

use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddIamBundle\Application\Query\FindUser\FindUserQuery;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\UserResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Context\Option\RequestOption;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProviderInterface;

final readonly class UserItemProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function provide(Operation $operation, Context $context): ?UserResource
    {
        $id = $context->get(RequestOption::class)
            ?->request()
            ->attributes
            ->getString('id');

        if ($id === null || $id === '') {
            return null;
        }

        return UserResource::fromModel(
            $this->queryBus->ask(new FindUserQuery(UserId::fromString($id))),
        );
    }
}
