<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * Authorisation changed. Both the new and the previous set travel with the
 * event: an audit reader wants the delta, and recomputing it from the
 * aggregate after the fact is impossible.
 */
final readonly class UserRolesChanged implements DomainEvent
{
    public function __construct(
        public string $userId,
        /** @var list<string> */
        public array $roles,
        /** @var list<string> */
        public array $previousRoles,
    ) {}
}
