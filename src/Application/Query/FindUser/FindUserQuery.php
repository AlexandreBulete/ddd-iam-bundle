<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Query\FindUser;

use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;

/**
 * @implements QueryInterface<User>
 */
final readonly class FindUserQuery implements QueryInterface
{
    public function __construct(public UserId $id) {}
}
