<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Service\PasswordHasherInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Password;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainPassword;
use AlexandreBulete\DddSymfonyBundle\Security\SecurityUser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Implements the {@see PasswordHasherInterface} port by delegating to
 * Symfony's hasher pipeline, configured in `security.yaml` against
 * {@see SecurityUser} — the class the firewall actually authenticates.
 *
 * Resolving the hasher by class rather than by user instance keeps this
 * adapter usable outside an authenticated context (CLI bootstrap, back-office
 * creation) while honouring whatever algorithm the application is configured
 * to use today.
 */
final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
    ) {}

    public function hash(PlainPassword $plain): Password
    {
        $hashed = $this->hasherFactory
            ->getPasswordHasher(SecurityUser::class)
            ->hash($plain->value());

        return new Password($hashed);
    }
}
