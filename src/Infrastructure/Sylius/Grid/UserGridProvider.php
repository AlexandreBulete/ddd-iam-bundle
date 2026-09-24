<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid;

use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddIamBundle\Application\Query\FindUsers\FindUsersQuery;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\UserResource;
use AlexandreBulete\DddSyliusBundle\Grid\GridPageResolver;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;

/**
 * Feeds the grid through the query bus rather than through Doctrine.
 *
 * The grid never touches the ORM: it asks a use case, gets a configured
 * repository back, and maps aggregates onto DTOs. That is what lets the same
 * read model serve a grid, an API and a CLI without three query paths.
 *
 * FixedAdapter because pagination already happened in the repository — letting
 * Pagerfanta slice again would paginate a page.
 */
final readonly class UserGridProvider implements DataProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    /**
     * @return PagerfantaInterface<UserResource>
     */
    public function getData(Grid $grid, Parameters $parameters): PagerfantaInterface
    {
        /** @var array<string, mixed> $criteria */
        $criteria = $parameters->get('criteria', []);

        /** @var array<string, string> $sorting */
        $sorting = $parameters->get('sorting', $grid->getSorting());

        $models = $this->queryBus->ask(new FindUsersQuery(
            page: GridPageResolver::getCurrentPage($grid, $parameters),
            itemsPerPage: GridPageResolver::getItemsPerPage($grid, $parameters),
            criteria: $criteria,
            withSorting: $sorting,
        ));

        $data = [];
        foreach ($models as $model) {
            $data[] = UserResource::fromModel($model);
        }

        return new Pagerfanta(new FixedAdapter($models->count(), $data));
    }
}
