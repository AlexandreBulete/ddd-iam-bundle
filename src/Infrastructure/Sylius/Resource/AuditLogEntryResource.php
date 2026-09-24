<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource;

use AlexandreBulete\DddIamBundle\Domain\Model\AuditLogEntry;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\AuditLogEntryGrid;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Metadata\Index;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Uid\AbstractUid;

/**
 * Read-only by construction: the only operation is Index. An audit log with an
 * edit button is not an audit log.
 */
#[AsResource(
    alias: 'iam.audit_log_entry',
    section: 'admin',
    templatesDir: '@SyliusAdminUi/crud',
    routePrefix: '/admin',
    driver: false,
    operations: [
        new Index(grid: AuditLogEntryGrid::class),
    ],
)]
final class AuditLogEntryResource implements ResourceInterface
{
    public function __construct(
        public ?AbstractUid $id = null,
        public ?string $eventType = null,
        public ?string $eventTypeShort = null,
        public ?string $userId = null,
        public ?string $payload = null,
        public ?\DateTimeImmutable $occurredAt = null,
    ) {}

    public function getId(): ?AbstractUid
    {
        return $this->id;
    }

    public static function fromModel(AuditLogEntry $entry): self
    {
        $eventType = $entry->eventType;
        $separator = strrpos($eventType, '\\');

        return new self(
            id: $entry->id,
            eventType: $eventType,
            // The FQCN is unreadable in a table cell; the short name is what an
            // admin scans for. Both are kept — the filter searches the FQCN.
            eventTypeShort: $separator === false ? $eventType : substr($eventType, $separator + 1),
            userId: $entry->userId,
            payload: $entry->payload,
            occurredAt: $entry->occurredAt,
        );
    }
}
