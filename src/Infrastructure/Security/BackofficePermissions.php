<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionProviderInterface;

/**
 * Entering the back office is a permission of its own — the application
 * requires it in access_control (`roles: backoffice.access`). No message
 * carries it: the firewall checks it.
 */
final readonly class BackofficePermissions implements PermissionProviderInterface
{
    public const ACCESS = 'backoffice.access';

    public function permissions(): array
    {
        return [self::ACCESS];
    }
}
