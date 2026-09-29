<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * A role now exists, with the permissions it grants.
 */
final readonly class RoleDefined implements DomainEvent
{
    public function __construct(
        public string $roleDefinitionId,
        public string $role,
        /** @var list<string> */
        public array $permissions,
    ) {}
}
