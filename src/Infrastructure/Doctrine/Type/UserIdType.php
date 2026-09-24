<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\GuidType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;

final class UserIdType extends GuidType
{
    public const NAME = 'iam_user_id';

    protected string $name = self::NAME;
    protected string $voClass = UserId::class;
}
