<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\RevokeAgent;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;

/**
 * Terminal: the agent and every token it holds stop working for good.
 *
 * @implements CommandInterface<Agent>
 */
final readonly class RevokeAgentCommand implements CommandInterface
{
    public function __construct(
        public AgentId $id,
    ) {}
}
