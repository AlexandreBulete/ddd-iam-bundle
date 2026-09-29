<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\GuidType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;

final class AgentIdType extends GuidType
{
    public const NAME = 'iam_agent_id';

    protected string $name = self::NAME;
    protected string $voClass = AgentId::class;
}
