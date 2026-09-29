<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\GuidType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;

final class RoleDefinitionIdType extends GuidType
{
    public const NAME = 'iam_role_definition_id';

    protected string $name = self::NAME;
    protected string $voClass = RoleDefinitionId::class;
}
