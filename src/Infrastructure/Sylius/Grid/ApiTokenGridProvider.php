<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid;

use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddIamBundle\Application\Query\FindAgents\FindAgentsQuery;
use AlexandreBulete\DddIamBundle\Application\Query\FindApiTokens\FindApiTokensQuery;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\ApiTokenResource;
use AlexandreBulete\DddSyliusBundle\Grid\GridPageResolver;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;
use Psr\Clock\ClockInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;

/**
 * Tokens with the name of their agent: seeing the tokens takes seeing the
 * agents too (`iam.find_api_tokens` and `iam.find_agents`).
 */
final readonly class ApiTokenGridProvider implements DataProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private ClockInterface $clock,
    ) {}

    /**
     * @return PagerfantaInterface<ApiTokenResource>
     */
    public function getData(Grid $grid, Parameters $parameters): PagerfantaInterface
    {
        /** @var array<string, string> $sorting */
        $sorting = $parameters->get('sorting', $grid->getSorting());

        $tokens = $this->queryBus->ask(new FindApiTokensQuery(
            page: GridPageResolver::getCurrentPage($grid, $parameters),
            itemsPerPage: GridPageResolver::getItemsPerPage($grid, $parameters),
            withSorting: $sorting,
        ));

        $names = [];
        foreach ($this->queryBus->ask(new FindAgentsQuery()) as $agent) {
            $names[(string) $agent->id] = $agent->name;
        }

        $now = $this->clock->now();
        $data = [];
        foreach ($tokens as $token) {
            $data[] = ApiTokenResource::fromModel($token, $names[(string) $token->agentId] ?? (string) $token->agentId, $now);
        }

        return new Pagerfanta(new FixedAdapter($tokens->count(), $data));
    }
}
