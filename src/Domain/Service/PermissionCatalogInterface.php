<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;

/**
 * Domain port — the permissions that exist in this application (ADR 0008:
 * one per use case, discovered, plus a few entry points).
 */
interface PermissionCatalogInterface
{
    public function all(): PermissionSet;

    /**
     * @throws \DomainException for a permission that does not exist
     */
    public function assertKnown(PermissionSet $permissions): void;
}
