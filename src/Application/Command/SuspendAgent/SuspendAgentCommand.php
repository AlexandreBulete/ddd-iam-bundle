<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\SuspendAgent;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;

/**
 * Cuts an agent off, reversibly: its tokens stop working until it is reactivated.
 *
 * @implements CommandInterface<Agent>
 */
final readonly class SuspendAgentCommand implements CommandInterface
{
    public function __construct(
        public AgentId $id,
    ) {}
}
