<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid;

use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddIamBundle\Application\Query\FindAuditLogs\FindAuditLogsQuery;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\AuditLogEntryResource;
use AlexandreBulete\DddSyliusBundle\Grid\GridPageResolver;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;

/**
 * Same wiring as {@see UserGridProvider}: query bus in, DTOs out.
 */
final readonly class AuditLogEntryGridProvider implements DataProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    /**
     * @return PagerfantaInterface<AuditLogEntryResource>
     */
    public function getData(Grid $grid, Parameters $parameters): PagerfantaInterface
    {
        /** @var array<string, mixed> $criteria */
        $criteria = $parameters->get('criteria', []);

        /** @var array<string, string> $sorting */
        $sorting = $parameters->get('sorting', $grid->getSorting());

        $repository = $this->queryBus->ask(new FindAuditLogsQuery(
            page: GridPageResolver::getCurrentPage($grid, $parameters),
            itemsPerPage: GridPageResolver::getItemsPerPage($grid, $parameters),
            criteria: $criteria,
            withSorting: $sorting,
        ));

        $data = [];
        foreach ($repository as $entry) {
            $data[] = AuditLogEntryResource::fromModel($entry);
        }

        return new Pagerfanta(new FixedAdapter($repository->count(), $data));
    }
}
