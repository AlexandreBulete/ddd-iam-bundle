<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Model;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\AuditLogEntryId;

/**
 * Append-only entity capturing every meaningful transition of a {@see User} —
 * the auditable history surfaced to admins for compliance and support.
 *
 * It has identity, speaks the ubiquitous language (event type, user, occurred
 * at) and represents a business concept ("what happened to whom, when"), so it
 * belongs to the Domain even though it carries no behaviour. Instances are
 * created exclusively by
 * {@see \AlexandreBulete\DddIamBundle\Application\Subscriber\AuditLogger}.
 *
 * `userId` is a plain string, not a UserId: the entry must outlive the user
 * row it describes, so it references it by value, never by association.
 */
final class AuditLogEntry
{
    private function __construct(
        private(set) AuditLogEntryId $id,
        private(set) string $eventType,
        private(set) string $userId,
        private(set) string $payload,
        private(set) \DateTimeImmutable $occurredAt,
    ) {}

    public static function record(
        AuditLogEntryId $id,
        string $eventType,
        string $userId,
        string $payload,
        \DateTimeImmutable $occurredAt,
    ): self {
        return new self($id, $eventType, $userId, $payload, $occurredAt);
    }
}
