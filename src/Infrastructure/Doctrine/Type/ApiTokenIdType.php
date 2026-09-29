<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\GuidType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenId;

final class ApiTokenIdType extends GuidType
{
    public const NAME = 'iam_api_token_id';

    protected string $name = self::NAME;
    protected string $voClass = ApiTokenId::class;
}
