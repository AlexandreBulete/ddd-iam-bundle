<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\RevokeAgent;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Domain\Exception\EntityNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\ApiTokenRepositoryInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class RevokeAgentHandler
{
    public function __construct(
        private AgentRepositoryInterface $agents,
        private ApiTokenRepositoryInterface $tokens,
        private ClockInterface $clock,
    ) {}

    public function __invoke(RevokeAgentCommand $command): Agent
    {
        $agent = $this->agents->findById($command->id)
            ?? throw new EntityNotFoundException(Agent::class, $command->id);

        $now = $this->clock->now();
        $agent->revoke($now);
        $this->agents->save($agent);

        // Refused anyway once the agent is revoked; revoked explicitly too, so
        // that each token says so on its own and in the journal's effects.
        foreach ($this->tokens->ofAgent($agent->id) as $token) {
            $token->revoke($now);
            $this->tokens->save($token);
        }

        return $agent;
    }
}
