<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\CreateAgent;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\GrantPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\IdentityGeneratorInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class CreateAgentHandler
{
    public function __construct(
        private AgentRepositoryInterface $agents,
        private RoleCatalogInterface $roleCatalog,
        private GrantPolicyInterface $grantPolicy,
        private IdentityGeneratorInterface $identities,
        private ClockInterface $clock,
    ) {}

    public function __invoke(CreateAgentCommand $command): Agent
    {
        // Like a user: no role unless given, and only roles one holds (ADR 0008).
        $roles = $command->roles ?? RoleSet::empty();
        $this->roleCatalog->assertKnown($roles);
        $this->grantPolicy->assertMayAssign($roles);

        $agent = Agent::create($this->identities->nextAgentId(), $command->name, $command->description, $roles, $this->clock->now());
        $this->agents->save($agent);

        return $agent;
    }
}
