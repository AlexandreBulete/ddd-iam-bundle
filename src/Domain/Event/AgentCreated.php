<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * An agent account exists: a non-human account, governed by roles like a person (ADR 0011).
 */
final readonly class AgentCreated implements DomainEvent
{
    public function __construct(
        public string $agentId,
        public string $name,
        /** @var list<string> */
        public array $roles,
    ) {}
}
