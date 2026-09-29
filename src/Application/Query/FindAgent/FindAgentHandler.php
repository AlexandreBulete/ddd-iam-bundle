<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindAgent;

use AlexandreBulete\DddFoundation\Application\Handler\QuerySingleHandler;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;

/**
 * @extends QuerySingleHandler<Agent>
 */
#[AsQueryHandler]
final readonly class FindAgentHandler extends QuerySingleHandler
{
    public function __construct(AgentRepositoryInterface $agents)
    {
        parent::__construct($agents);
    }

    public function __invoke(FindAgentQuery $query): Agent
    {
        return $this->build($query);
    }
}
