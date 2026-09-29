<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\CreateAgent;

use AlexandreBulete\DddFoundation\Application\Activity\ActivityDescription;
use AlexandreBulete\DddFoundation\Application\Activity\JournaledInterface;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;

/**
 * @implements CommandInterface<Agent>
 */
final readonly class CreateAgentCommand implements CommandInterface, JournaledInterface
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?RoleSet $roles = null,
    ) {}

    public function describeActivity(): ActivityDescription
    {
        return new ActivityDescription(
            summary: 'iam.activity.agent_created',
            summaryParams: ['agent' => $this->name],
            details: ['roles' => $this->roles?->toStrings() ?? []],
        );
    }
}
