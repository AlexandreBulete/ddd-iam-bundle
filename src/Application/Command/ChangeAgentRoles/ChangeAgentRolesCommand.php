<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ChangeAgentRoles;

use AlexandreBulete\DddFoundation\Application\Activity\ActivityDescription;
use AlexandreBulete\DddFoundation\Application\Activity\JournaledInterface;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;

/**
 * Replaces the agent's whole role set.
 *
 * @implements CommandInterface<Agent>
 */
final readonly class ChangeAgentRolesCommand implements CommandInterface, JournaledInterface
{
    public function __construct(
        public AgentId $id,
        public RoleSet $roles,
    ) {}

    public function describeActivity(): ActivityDescription
    {
        return new ActivityDescription(
            subjectType: 'agent',
            subjectId: (string) $this->id,
            summary: 'iam.activity.agent_roles_changed',
            details: ['roles' => $this->roles->toStrings()],
        );
    }
}
