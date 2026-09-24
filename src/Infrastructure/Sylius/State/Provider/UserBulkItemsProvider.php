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

final readonly class UserBulkItemsProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    /**
     * @return list<UserResource>
     */
    public function provide(Operation $operation, Context $context): array
    {
        /** @var list<string> $ids */
        $ids = $context->get(RequestOption::class)
            ?->request()
            ->request
            ->all('ids') ?? [];

        $resources = [];
        foreach ($ids as $id) {
            $resources[] = UserResource::fromModel(
                $this->queryBus->ask(new FindUserQuery(UserId::fromString($id))),
            );
        }

        return $resources;
    }
}
