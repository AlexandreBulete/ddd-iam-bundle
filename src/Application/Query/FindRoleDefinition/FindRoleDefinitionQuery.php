<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindRoleDefinition;

use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;

/**
 * @implements QueryInterface<RoleDefinition>
 */
final readonly class FindRoleDefinitionQuery implements QueryInterface
{
    public function __construct(
        public RoleDefinitionId $id,
    ) {}
}
