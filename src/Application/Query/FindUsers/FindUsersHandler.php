<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindUsers;

use AlexandreBulete\DddFoundation\Application\Handler\QueryCollectionHandler;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;

#[AsQueryHandler]
final readonly class FindUsersHandler extends QueryCollectionHandler
{
    public function __construct(UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    /**
     * @return RepositoryInterface<User>
     */
    public function __invoke(FindUsersQuery $query): RepositoryInterface
    {
        return $this->build($query);
    }
}
