<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Exception;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;

/**
 * A role cannot be removed while users carry it: they would silently lose
 * access, and the reason would be invisible on their account.
 */
final class RoleStillAssignedException extends \DomainException
{
    public function __construct(Role $role, public readonly int $users)
    {
        parent::__construct(sprintf('%s is still assigned to %d user(s): take it off them first.', $role->value(), $users));
    }
}
