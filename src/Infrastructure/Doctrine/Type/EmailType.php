<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\VarcharType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;

final class EmailType extends VarcharType
{
    public const NAME = 'iam_user_email';

    protected string $name = self::NAME;
    protected string $voClass = Email::class;
}
