<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindRoleDefinition;

use AlexandreBulete\DddFoundation\Application\Handler\QuerySingleHandler;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;

/**
 * @extends QuerySingleHandler<RoleDefinition>
 */
#[AsQueryHandler]
final readonly class FindRoleDefinitionHandler extends QuerySingleHandler
{
    public function __construct(RoleDefinitionRepositoryInterface $roles)
    {
        parent::__construct($roles);
    }

    public function __invoke(FindRoleDefinitionQuery $query): RoleDefinition
    {
        return $this->build($query);
    }
}
