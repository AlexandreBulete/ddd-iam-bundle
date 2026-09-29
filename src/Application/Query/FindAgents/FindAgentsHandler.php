<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindAgents;

use AlexandreBulete\DddFoundation\Application\Handler\QueryCollectionHandler;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;

/**
 * @extends QueryCollectionHandler<Agent>
 */
#[AsQueryHandler]
final readonly class FindAgentsHandler extends QueryCollectionHandler
{
    public function __construct(AgentRepositoryInterface $agents)
    {
        parent::__construct($agents);
    }

    /**
     * @return RepositoryInterface<Agent>
     */
    public function __invoke(FindAgentsQuery $query): RepositoryInterface
    {
        return $this->build($query);
    }
}
