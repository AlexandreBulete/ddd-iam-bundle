<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\ValueObject;

use AlexandreBulete\DddFoundation\Domain\ValueObject\StringVO;

/**
 * Plain-text password — transient input only.
 *
 * Holds the structural invariant of a password input (non-empty). Business
 * rules (min length, complexity, history…) are intentionally NOT here: they
 * belong to {@see \AlexandreBulete\DddIamBundle\Domain\Service\PasswordPolicyInterface},
 * which the Application handlers invoke before hashing.
 *
 * That split is what lets the VO stay stable across deployments while each
 * deployment tunes its own policy through `iam.password_policy`.
 *
 * Never store, log or serialize an instance of this VO.
 */
final readonly class PlainPassword extends StringVO
{
    protected function validate(string $value): void
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Password cannot be empty');
        }
    }

    public function __toString(): string
    {
        return '***';
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => '***'];
    }
}
