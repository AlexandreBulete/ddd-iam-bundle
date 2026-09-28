<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Subscriber;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;
use AlexandreBulete\DddIamBundle\Domain\Event\UserCreated;
use AlexandreBulete\DddIamBundle\Domain\Event\UserEmailChanged;
use AlexandreBulete\DddIamBundle\Domain\Event\UserPasswordChanged;
use AlexandreBulete\DddIamBundle\Domain\Event\UserReactivated;
use AlexandreBulete\DddIamBundle\Domain\Event\UserRenamed;
use AlexandreBulete\DddIamBundle\Domain\Event\UserRevoked;
use AlexandreBulete\DddIamBundle\Domain\Event\UserRolesChanged;
use AlexandreBulete\DddIamBundle\Domain\Event\UserSuspended;
use AlexandreBulete\DddIamBundle\Domain\Model\AuditLogEntry;
use AlexandreBulete\DddIamBundle\Domain\Repository\AuditLogEntryRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\EventSerializerInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\IdentityGeneratorInterface;
use Psr\Clock\ClockInterface;

/**
 * Materialises every IAM domain event into an {@see AuditLogEntry} row — the
 * consultable history of who did what to which account.
 *
 * Registered only when `iam.audit.enabled` is true; the service definition
 * itself is conditional, so a deployment that does not want an audit trail
 * pays nothing for it (no listener, no table write).
 *
 * One method per event rather than a single catch-all, each subscribed in
 * config/services_audit.php — not by attribute, which would tie this
 * Application class to the framework. Adding an event to the bundle without
 * adding it there is a visible omission rather than a silent one.
 *
 * Runs inside the same transaction as the aggregate write (see
 * {@see \AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineUserRepository::save()}),
 * so an account change and its audit row commit or roll back together.
 */
final readonly class AuditLogger
{
    public function __construct(
        private AuditLogEntryRepositoryInterface $entries,
        private EventSerializerInterface $serializer,
        private IdentityGeneratorInterface $identities,
        private ClockInterface $clock,
    ) {}

    public function onUserCreated(UserCreated $event): void
    {
        $this->log($event, $event->userId);
    }

    public function onUserRenamed(UserRenamed $event): void
    {
        $this->log($event, $event->userId);
    }

    public function onUserEmailChanged(UserEmailChanged $event): void
    {
        $this->log($event, $event->userId);
    }

    public function onUserPasswordChanged(UserPasswordChanged $event): void
    {
        $this->log($event, $event->userId);
    }

    public function onUserRolesChanged(UserRolesChanged $event): void
    {
        $this->log($event, $event->userId);
    }

    public function onUserSuspended(UserSuspended $event): void
    {
        $this->log($event, $event->userId);
    }

    public function onUserReactivated(UserReactivated $event): void
    {
        $this->log($event, $event->userId);
    }

    public function onUserRevoked(UserRevoked $event): void
    {
        $this->log($event, $event->userId);
    }

    private function log(DomainEvent $event, string $userId): void
    {
        $this->entries->add(AuditLogEntry::record(
            id: $this->identities->nextAuditLogEntryId(),
            eventType: $event::class,
            userId: $userId,
            payload: $this->serializer->serialize($event),
            occurredAt: $this->clock->now(),
        ));
    }
}
