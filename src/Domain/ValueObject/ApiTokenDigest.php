<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\ValueObject;

use AlexandreBulete\DddFoundation\Domain\ValueObject\StringVO;

/**
 * What is kept of a token's secret: its SHA-256, never the secret (ADR 0011).
 *
 * A fast hash on purpose. A slow one (bcrypt, argon2) protects a low-entropy
 * secret — a password a person chose; this secret is 256 random bits, so a
 * slow hash would only slow down every legitimate request.
 */
final readonly class ApiTokenDigest extends StringVO
{
    public static function of(string $secret): self
    {
        return new self(hash('sha256', $secret));
    }

    /**
     * In constant time: how long a comparison takes must not tell how much of
     * a guess was right.
     */
    public function matches(string $secret): bool
    {
        return hash_equals($this->value(), hash('sha256', $secret));
    }

    protected function validate(string $value): void
    {
        if (preg_match('/^[0-9a-f]{64}$/', $value) !== 1) {
            throw new \InvalidArgumentException('A token digest is a SHA-256, in hexadecimal.');
        }
    }
}
