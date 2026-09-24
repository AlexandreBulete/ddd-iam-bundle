<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindUser;

use AlexandreBulete\DddFoundation\Application\Handler\QuerySingleHandler;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;

/**
 * @extends QuerySingleHandler<User>
 */
#[AsQueryHandler]
final readonly class FindUserHandler extends QuerySingleHandler
{
    public function __construct(UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    public function __invoke(FindUserQuery $query): User
    {
        return $this->build($query);
    }
}
