<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindAgents;

use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;

/**
 * @implements QueryInterface<AgentRepositoryInterface>
 */
final readonly class FindAgentsQuery implements QueryInterface
{
    /**
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $withSorting
     */
    public function __construct(
        public ?int $page = null,
        public ?int $itemsPerPage = null,
        public array $criteria = [],
        public array $withSorting = [],
    ) {}
}
