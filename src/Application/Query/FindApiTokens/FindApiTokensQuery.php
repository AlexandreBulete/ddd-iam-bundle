<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindApiTokens;

use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\ApiTokenRepositoryInterface;

/**
 * @implements QueryInterface<ApiTokenRepositoryInterface>
 */
final readonly class FindApiTokensQuery implements QueryInterface
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
