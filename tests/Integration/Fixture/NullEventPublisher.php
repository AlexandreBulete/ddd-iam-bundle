<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Integration\Fixture;

use AlexandreBulete\DddIamBundle\Domain\Service\DomainEventPublisherInterface;

final class NullEventPublisher implements DomainEventPublisherInterface
{
    public function publishAll(iterable $events): void
    {
    }
}
