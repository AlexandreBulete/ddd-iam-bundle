<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\DescribeAgent;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;

/**
 * Names and describes an agent: what it is for, who runs it.
 *
 * @implements CommandInterface<Agent>
 */
final readonly class DescribeAgentCommand implements CommandInterface
{
    public function __construct(
        public AgentId $id,
        public string $name,
        public ?string $description = null,
    ) {}
}
