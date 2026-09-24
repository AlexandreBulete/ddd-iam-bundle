<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * Password rotation notification — the payload deliberately omits the new
 * password, hashed or plain. Subscribers learn that credentials changed;
 * they never need the secret itself to act (audit, session invalidation…).
 */
final readonly class UserPasswordChanged implements DomainEvent
{
    public function __construct(
        public string $userId,
    ) {}
}
