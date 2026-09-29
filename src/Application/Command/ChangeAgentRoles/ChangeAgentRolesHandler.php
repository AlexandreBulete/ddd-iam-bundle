<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ChangeAgentRoles;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Domain\Exception\EntityNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\GrantPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class ChangeAgentRolesHandler
{
    public function __construct(
        private AgentRepositoryInterface $agents,
        private RoleCatalogInterface $roleCatalog,
        private GrantPolicyInterface $grantPolicy,
        private ClockInterface $clock,
    ) {}

    public function __invoke(ChangeAgentRolesCommand $command): Agent
    {
        $agent = $this->agents->findById($command->id)
            ?? throw new EntityNotFoundException(Agent::class, $command->id);

        $this->roleCatalog->assertKnown($command->roles);
        // Taking a role away changes access too: see ChangeUserRolesHandler.
        $this->grantPolicy->assertMayAssign($command->roles->union($agent->roles));

        $agent->changeRoles($command->roles, $this->clock->now());
        $this->agents->save($agent);

        return $agent;
    }
}
