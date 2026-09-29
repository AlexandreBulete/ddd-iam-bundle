<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

final readonly class RoleDefinitionRelabeled implements DomainEvent
{
    public function __construct(
        public string $roleDefinitionId,
        public string $label,
    ) {}
}
