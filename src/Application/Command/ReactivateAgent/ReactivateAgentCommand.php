<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ReactivateAgent;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;

/**
 * @implements CommandInterface<Agent>
 */
final readonly class ReactivateAgentCommand implements CommandInterface
{
    public function __construct(
        public AgentId $id,
    ) {}
}
