<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * What a role grants changed. Both sets travel with the event: the delta
 * cannot be rebuilt from the aggregate afterwards.
 */
final readonly class RolePermissionsChanged implements DomainEvent
{
    public function __construct(
        public string $roleDefinitionId,
        public string $role,
        /** @var list<string> */
        public array $permissions,
        /** @var list<string> */
        public array $previousPermissions,
    ) {}
}
