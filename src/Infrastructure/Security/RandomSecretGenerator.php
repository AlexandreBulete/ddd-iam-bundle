<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Service\SecretGeneratorInterface;

/**
 * 32 bytes from the system CSPRNG, base64url without padding: 43 characters
 * that survive a URL, a header and an environment variable unescaped.
 */
final readonly class RandomSecretGenerator implements SecretGeneratorInterface
{
    public function secret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
