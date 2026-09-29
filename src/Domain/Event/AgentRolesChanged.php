<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * Both sets travel with the event, like UserRolesChanged.
 */
final readonly class AgentRolesChanged implements DomainEvent
{
    public function __construct(
        public string $agentId,
        /** @var list<string> */
        public array $roles,
        /** @var list<string> */
        public array $previousRoles,
    ) {}
}
