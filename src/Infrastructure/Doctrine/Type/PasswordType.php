<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\VarcharType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Password;

final class PasswordType extends VarcharType
{
    public const NAME = 'iam_user_password';

    protected string $name = self::NAME;
    protected string $voClass = Password::class;
}
