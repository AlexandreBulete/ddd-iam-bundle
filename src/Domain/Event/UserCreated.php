<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * A user account came into existence. Carries enough context for a subscriber
 * to provision downstream state (welcome mail, directory sync, companion
 * aggregate) without re-reading the aggregate.
 */
final readonly class UserCreated implements DomainEvent
{
    public function __construct(
        public string $userId,
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
        /** @var list<string> */
        public array $roles,
    ) {}
}
