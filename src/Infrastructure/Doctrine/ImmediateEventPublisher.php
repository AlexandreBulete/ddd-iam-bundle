<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine;

use AlexandreBulete\DddFoundation\Application\Event\EventDispatcherInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\DomainEventPublisherInterface;

/**
 * Default adapter for {@see DomainEventPublisherInterface}: hands the events
 * straight to the application dispatcher, inside the caller's transaction.
 *
 * Good enough for in-process subscribers such as the audit logger — the event
 * and the aggregate write commit together. It is NOT good enough for
 * cross-process delivery: a subscriber publishing to a broker from here would
 * emit before the commit, and a rollback would leave the outside world
 * believing something that never happened.
 *
 * A project that needs that guarantee binds its own outbox-backed adapter onto
 * {@see DomainEventPublisherInterface} and changes nothing else in the bundle.
 */
final readonly class ImmediateEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function publishAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
