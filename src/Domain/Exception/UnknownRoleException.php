<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Exception;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;

/**
 * Raised when a role is granted that no deployment declared in `iam.roles`.
 *
 * Typos in a role name are otherwise invisible — Symfony Security simply
 * never matches the role, and the user silently loses access instead of
 * failing loudly at the point of the mistake.
 */
final class UnknownRoleException extends \DomainException
{
    /**
     * @param list<string> $known
     */
    public function __construct(Role $role, public readonly array $known)
    {
        parent::__construct(sprintf(
            'Unknown role "%s". Declared roles: %s. Add it under `iam.roles` to grant it.',
            $role->value(),
            $known === [] ? '(none)' : implode(', ', $known),
        ));
    }
}
