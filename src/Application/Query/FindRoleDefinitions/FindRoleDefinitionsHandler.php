<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindRoleDefinitions;

use AlexandreBulete\DddFoundation\Application\Handler\QueryCollectionHandler;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;

/**
 * @extends QueryCollectionHandler<RoleDefinition>
 */
#[AsQueryHandler]
final readonly class FindRoleDefinitionsHandler extends QueryCollectionHandler
{
    public function __construct(RoleDefinitionRepositoryInterface $roles)
    {
        parent::__construct($roles);
    }

    /**
     * @return RepositoryInterface<RoleDefinition>
     */
    public function __invoke(FindRoleDefinitionsQuery $query): RepositoryInterface
    {
        return $this->build($query);
    }
}
