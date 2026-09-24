<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\ValueObject;

use AlexandreBulete\DddFoundation\Domain\ValueObject\StringVO;

/**
 * Hashed password — opaque value stored on the {@see \AlexandreBulete\DddIamBundle\Domain\Model\User} aggregate.
 *
 * The Domain never sees plain text: instances are produced exclusively by
 * {@see \AlexandreBulete\DddIamBundle\Domain\Service\PasswordHasherInterface::hash()}
 * from a {@see PlainPassword}. No length or complexity rule applies here —
 * those belong to {@see PlainPassword} and to the policy port.
 */
final readonly class Password extends StringVO
{
    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Hashed password cannot be empty');
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
