<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindApiTokens;

use AlexandreBulete\DddFoundation\Application\Handler\QueryCollectionHandler;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Domain\Repository\ApiTokenRepositoryInterface;

/**
 * @extends QueryCollectionHandler<ApiToken>
 */
#[AsQueryHandler]
final readonly class FindApiTokensHandler extends QueryCollectionHandler
{
    public function __construct(ApiTokenRepositoryInterface $tokens)
    {
        parent::__construct($tokens);
    }

    /**
     * @return RepositoryInterface<ApiToken>
     */
    public function __invoke(FindApiTokensQuery $query): RepositoryInterface
    {
        return $this->build($query);
    }
}
