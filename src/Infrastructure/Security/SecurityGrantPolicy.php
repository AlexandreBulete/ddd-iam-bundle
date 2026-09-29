<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\GrantPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceContext;

/**
 * No escalation: whoever acts — read from the trace of the message being
 * handled — hands out only what they hold. The system (a migration, the CLI
 * bootstrap) is not limited.
 */
final readonly class SecurityGrantPolicy implements GrantPolicyInterface
{
    public function __construct(
        private TraceContext $trace,
        private AccountPermissions $permissions,
        private RoleDefinitionRepositoryInterface $roles,
    ) {}

    public function assertMayGrant(PermissionSet $permissions): void
    {
        $access = $this->actingAccess();
        if ($access === true || ($access !== null && $access->grantsAll($permissions))) {
            return;
        }

        $held = $access === null ? [] : $access->permissions->toArray();
        throw new \DomainException(sprintf(
            'You cannot hand out permissions you do not hold: %s.',
            implode(', ', array_diff($permissions->toArray(), $held)),
        ));
    }

    public function assertMayAssign(RoleSet $roles): void
    {
        $access = $this->actingAccess();
        if ($access === true) {
            return;
        }

        foreach ($roles as $role) {
            $definition = $this->roles->findByRole($role);
            if ($definition === null) {
                continue; // unknown roles are the catalog's to refuse
            }

            $allowed = $access !== null && ($definition->system ? $access->superAdmin : $access->grantsAll($definition->permissions));
            if (!$allowed) {
                throw new \DomainException(sprintf('You cannot assign %s: it grants more than you hold.', $definition->label));
            }
        }
    }

    /**
     * true for the system, the acting account's access otherwise (null: none).
     */
    private function actingAccess(): AccountAccess|true|null
    {
        $actor = $this->trace->current()?->actor;
        if ($actor === null || $actor->isSystem()) {
            return true;
        }

        return $this->permissions->of($actor);
    }
}
