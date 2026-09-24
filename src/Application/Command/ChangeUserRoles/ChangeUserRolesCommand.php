<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ChangeUserRoles;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;

/**
 * Replaces the user's whole role set.
 *
 * @implements CommandInterface<User>
 */
final readonly class ChangeUserRolesCommand implements CommandInterface
{
    public function __construct(
        public UserId $id,
        public RoleSet $roles,
    ) {}
}
