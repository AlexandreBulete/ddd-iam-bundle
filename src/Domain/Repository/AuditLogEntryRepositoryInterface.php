<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Repository;

use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\AuditLogEntry;

/**
 * @extends RepositoryInterface<AuditLogEntry>
 */
interface AuditLogEntryRepositoryInterface extends RepositoryInterface
{
    /**
     * Append-only: an entry is added, never updated nor removed.
     */
    public function add(AuditLogEntry $entry): void;
}
