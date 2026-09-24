<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

final readonly class UserReactivated implements DomainEvent
{
    public function __construct(
        public string $userId,
    ) {}
}
