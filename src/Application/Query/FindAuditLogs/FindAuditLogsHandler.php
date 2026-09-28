<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindAuditLogs;

use AlexandreBulete\DddFoundation\Application\Handler\QueryCollectionHandler;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\AuditLogEntry;
use AlexandreBulete\DddIamBundle\Domain\Repository\AuditLogEntryRepositoryInterface;

/**
 * @extends QueryCollectionHandler<AuditLogEntry>
 */
#[AsQueryHandler]
final readonly class FindAuditLogsHandler extends QueryCollectionHandler
{
    public function __construct(AuditLogEntryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return RepositoryInterface<AuditLogEntry>
     */
    public function __invoke(FindAuditLogsQuery $query): RepositoryInterface
    {
        return $this->build($query);
    }
}
