<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ReactivateUser;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;

/**
 * @implements CommandInterface<User>
 */
final readonly class ReactivateUserCommand implements CommandInterface
{
    public function __construct(
        public UserId $id,
    ) {}
}
