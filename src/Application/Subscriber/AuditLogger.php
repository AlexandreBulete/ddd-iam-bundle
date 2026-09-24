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
use AlexandreBulete\DddIamBundle\Domain\Service\EventSerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Ulid;

/**
 * Materialises every IAM domain event into an {@see AuditLogEntry} row — the
 * consultable history of who did what to which account.
 *
 * Registered only when `iam.audit.enabled` is true; the service definition
 * itself is conditional, so a deployment that does not want an audit trail
 * pays nothing for it (no listener, no table write).
 *
 * One `#[AsEventListener]` method per event rather than a single catch-all:
 * Symfony registers listeners by the event's FQCN, and being explicit means
 * adding an event to the bundle without adding it here is a visible omission
 * rather than a silent one.
 *
 * Runs inside the same transaction as the aggregate write (see
 * {@see \AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineUserRepository::save()}),
 * so an account change and its audit row commit or roll back together.
 */
final readonly class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventSerializerInterface $serializer,
        private ClockInterface $clock,
    ) {}

    #[AsEventListener]
    public function onUserCreated(UserCreated $event): void
    {
        $this->log($event, $event->userId);
    }

    #[AsEventListener]
    public function onUserRenamed(UserRenamed $event): void
    {
        $this->log($event, $event->userId);
    }

    #[AsEventListener]
    public function onUserEmailChanged(UserEmailChanged $event): void
    {
        $this->log($event, $event->userId);
    }

    #[AsEventListener]
    public function onUserPasswordChanged(UserPasswordChanged $event): void
    {
        $this->log($event, $event->userId);
    }

    #[AsEventListener]
    public function onUserRolesChanged(UserRolesChanged $event): void
    {
        $this->log($event, $event->userId);
    }

    #[AsEventListener]
    public function onUserSuspended(UserSuspended $event): void
    {
        $this->log($event, $event->userId);
    }

    #[AsEventListener]
    public function onUserReactivated(UserReactivated $event): void
    {
        $this->log($event, $event->userId);
    }

    #[AsEventListener]
    public function onUserRevoked(UserRevoked $event): void
    {
        $this->log($event, $event->userId);
    }

    private function log(DomainEvent $event, string $userId): void
    {
        $entry = new AuditLogEntry(
            id: new Ulid(),
            eventType: $event::class,
            userId: $userId,
            payload: $this->serializer->serialize($event),
            occurredAt: $this->clock->now(),
        );

        $this->em->persist($entry);
        $this->em->flush();
    }
}
