<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\VarcharType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserStatus;

final class UserStatusType extends VarcharType
{
    public const NAME = 'iam_user_status';

    protected string $name = self::NAME;
    protected int $length = 20;
    protected string $voClass = UserStatus::class;
}
