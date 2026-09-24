<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\ValueObject;

use AlexandreBulete\DddFoundation\Domain\ValueObject\StringVO;
use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;

final readonly class UserStatus extends StringVO
{
    public static function fromEnum(UserStatusEnum $status): self
    {
        return new self($status->value);
    }

    public function toEnum(): UserStatusEnum
    {
        return UserStatusEnum::from($this->value());
    }

    protected function validate(string $value): void
    {
        if (UserStatusEnum::tryFrom($value) === null) {
            throw new \InvalidArgumentException(sprintf('Invalid user status "%s".', $value));
        }
    }

    public function canLogin(): bool
    {
        return $this->toEnum() === UserStatusEnum::ACTIVE;
    }

    public function isActive(): bool
    {
        return $this->toEnum() === UserStatusEnum::ACTIVE;
    }

    public function isRevoked(): bool
    {
        return $this->toEnum() === UserStatusEnum::REVOKED;
    }

    public function isSuspended(): bool
    {
        return $this->toEnum() === UserStatusEnum::SUSPENDED;
    }
}
