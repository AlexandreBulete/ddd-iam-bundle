<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\ValueObject;

use AlexandreBulete\DddFoundation\Domain\ValueObject\EmailVO;

/**
 * The login identity. Stored lowercase: an email is used case-insensitively
 * in practice, and `Ada@example.com` must not become a second account next to
 * `ada@example.com` under the unique constraint.
 */
final readonly class Email extends EmailVO
{
    protected function normalize(string $value): string
    {
        return mb_strtolower(parent::normalize($value));
    }
}
