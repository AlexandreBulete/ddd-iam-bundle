<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\VarcharType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;

final class RoleType extends VarcharType
{
    public const NAME = 'iam_role';

    protected string $name = self::NAME;
    protected int $length = 64;
    protected string $voClass = Role::class;
}
