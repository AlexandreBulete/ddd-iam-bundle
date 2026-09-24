<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Model;

use Symfony\Component\Uid\Ulid;

/**
 * Append-only entity capturing every meaningful transition of a {@see User} —
 * the auditable history surfaced to admins for compliance and support.
 *
 * It has identity, speaks the ubiquitous language (event type, user, occurred
 * at) and represents a business concept ("what happened to whom, when"), so it
 * belongs to the Domain even though it carries no behaviour. Instances are
 * created exclusively by
 * {@see \AlexandreBulete\DddIamBundle\Application\Subscriber\AuditLogger}.
 */
final class AuditLogEntry
{
    public function __construct(
        private(set) Ulid $id,
        private(set) string $eventType,
        private(set) string $userId,
        private(set) string $payload,
        private(set) \DateTimeImmutable $occurredAt,
    ) {}
}
