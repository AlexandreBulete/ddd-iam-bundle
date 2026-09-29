<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid;

use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddIamBundle\Application\Query\FindRoleDefinitions\FindRoleDefinitionsQuery;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\RoleDefinitionResource;
use AlexandreBulete\DddSyliusBundle\Grid\GridPageResolver;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;

final readonly class RoleDefinitionGridProvider implements DataProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    /**
     * @return PagerfantaInterface<RoleDefinitionResource>
     */
    public function getData(Grid $grid, Parameters $parameters): PagerfantaInterface
    {
        /** @var array<string, string> $sorting */
        $sorting = $parameters->get('sorting', $grid->getSorting());

        $definitions = $this->queryBus->ask(new FindRoleDefinitionsQuery(
            page: GridPageResolver::getCurrentPage($grid, $parameters),
            itemsPerPage: GridPageResolver::getItemsPerPage($grid, $parameters),
            withSorting: $sorting,
        ));

        $data = [];
        foreach ($definitions as $definition) {
            $data[] = RoleDefinitionResource::fromModel($definition);
        }

        return new Pagerfanta(new FixedAdapter($definitions->count(), $data));
    }
}
