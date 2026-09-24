<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Serializer;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;
use AlexandreBulete\DddIamBundle\Domain\Service\EventSerializerInterface;

/**
 * Renders a domain event as JSON, from its public properties.
 *
 * Deliberately reflection-free and Symfony-Serializer-free: IAM events are
 * flat readonly DTOs of scalars, `get_object_vars()` covers them exactly, and
 * pulling in a normalizer stack would add a dependency plus a configuration
 * surface for a payload nobody ever deserializes back into an object.
 *
 * The audit payload is a human-readable record, not a wire format. If a
 * project needs a versioned, machine-consumable envelope, it aliases its own
 * implementation onto {@see EventSerializerInterface}.
 */
final readonly class JsonEventSerializer implements EventSerializerInterface
{
    public function serialize(DomainEvent $event): string
    {
        return json_encode(
            get_object_vars($event),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
