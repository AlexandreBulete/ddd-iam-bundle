<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Exception;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;

/**
 * Raised when a role is granted that is not defined.
 *
 * Otherwise invisible: the user would silently have no access instead of the
 * mistake failing loudly where it is made.
 */
final class UnknownRoleException extends \DomainException
{
    /**
     * @param list<string> $known
     */
    public function __construct(Role $role, public readonly array $known)
    {
        parent::__construct(sprintf(
            'Unknown role "%s". Defined roles: %s. Define it in the back office (Roles) to grant it.',
            $role->value(),
            $known === [] ? '(none)' : implode(', ', $known),
        ));
    }
}
