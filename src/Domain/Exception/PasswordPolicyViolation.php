<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Exception;

/**
 * Raised when a plaintext password fails one or more rules of the configured
 * {@see \AlexandreBulete\DddIamBundle\Domain\Service\PasswordPolicyInterface}.
 *
 * Carries the full list of violations so UI and CLI layers can surface them
 * all at once, rather than leaking the policy one rule per attempt.
 */
final class PasswordPolicyViolation extends \DomainException
{
    /**
     * @param list<string> $violations human-readable, ubiquitous-language rule messages
     */
    public function __construct(
        public readonly array $violations,
    ) {
        parent::__construct(
            $violations === []
                ? 'Password policy violated'
                : implode(' ', $violations),
        );
    }
}
