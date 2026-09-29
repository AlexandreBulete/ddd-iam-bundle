<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Service\PermissionCatalogInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;

/**
 * The permissions that exist, as discovered by ddd-symfony-bundle.
 */
final readonly class RegistryPermissionCatalog implements PermissionCatalogInterface
{
    public function __construct(
        private PermissionRegistry $registry,
    ) {}

    public function all(): PermissionSet
    {
        return PermissionSet::of($this->registry->all());
    }

    public function assertKnown(PermissionSet $permissions): void
    {
        $unknown = array_values(array_diff($permissions->toArray(), $this->registry->all()));
        if ($unknown !== []) {
            throw new \DomainException(sprintf('Unknown permission(s): %s.', implode(', ', $unknown)));
        }
    }
}
