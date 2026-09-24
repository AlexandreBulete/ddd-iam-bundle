<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * Domain port — renders a domain event as the opaque payload stored on an
 * {@see \AlexandreBulete\DddIamBundle\Domain\Model\AuditLogEntry}.
 *
 * Owned by the bundle rather than borrowed from the host application: an
 * audit log that cannot be written because the project has not yet defined a
 * serializer is an audit log nobody turns on.
 */
interface EventSerializerInterface
{
    public function serialize(DomainEvent $event): string;
}
