<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindAgent;

use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;

/**
 * @implements QueryInterface<Agent>
 */
final readonly class FindAgentQuery implements QueryInterface
{
    public function __construct(public AgentId $id) {}
}
