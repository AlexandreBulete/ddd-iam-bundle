<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ReactivateAgent;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Domain\Exception\EntityNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class ReactivateAgentHandler
{
    public function __construct(
        private AgentRepositoryInterface $agents,
        private ClockInterface $clock,
    ) {}

    public function __invoke(ReactivateAgentCommand $command): Agent
    {
        $agent = $this->agents->findById($command->id)
            ?? throw new EntityNotFoundException(Agent::class, $command->id);

        $agent->reactivate($this->clock->now());
        $this->agents->save($agent);

        return $agent;
    }
}
