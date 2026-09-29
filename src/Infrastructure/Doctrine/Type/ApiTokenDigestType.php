<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\VarcharType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenDigest;

final class ApiTokenDigestType extends VarcharType
{
    public const NAME = 'iam_api_token_digest';

    protected string $name = self::NAME;
    protected int $length = 64;
    protected string $voClass = ApiTokenDigest::class;
}
