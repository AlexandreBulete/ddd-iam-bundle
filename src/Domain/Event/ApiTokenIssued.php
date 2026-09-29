<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Event;

use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;

/**
 * A token was issued. Its secret is not here, nor anywhere else after issuance.
 */
final readonly class ApiTokenIssued implements DomainEvent
{
    public function __construct(
        public string $tokenId,
        public string $agentId,
        public string $label,
        public \DateTimeImmutable $expiresAt,
    ) {}
}
