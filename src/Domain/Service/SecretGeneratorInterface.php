<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

/**
 * Domain port — draws the secret of a new token: cryptographically random,
 * URL-safe, at least 256 bits.
 */
interface SecretGeneratorInterface
{
    public function secret(): string;
}
