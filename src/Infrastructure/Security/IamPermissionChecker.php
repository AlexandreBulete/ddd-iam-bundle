<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionCheckerInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;

/**
 * What turns authorization on (ADR 0008): ddd-symfony-bundle installs its
 * middleware as soon as this exists.
 */
final readonly class IamPermissionChecker implements PermissionCheckerInterface
{
    public function __construct(
        private AccountPermissions $permissions,
    ) {}

    public function isGranted(Actor $actor, string $permission): bool
    {
        return $this->permissions->of($actor)?->grants($permission) ?? false;
    }
}
