<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * Domain port — publishes the events an aggregate recorded, once it is safely
 * persisted.
 *
 * The bundle ships an immediate, in-transaction publisher. A project running
 * the transactional-outbox pattern binds its own adapter here — writing the
 * events to its outbox table inside the same transaction — and the bundle
 * neither knows nor cares. That is why the repository depends on this port
 * and not on an outbox writer.
 *
 * @see \AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\ImmediateEventPublisher
 */
interface DomainEventPublisherInterface
{
    /**
     * @param iterable<DomainEvent> $events
     */
    public function publishAll(iterable $events): void;
}
