<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Resolves an actor to what its account may do — once per request: every
 * command and query is checked, and roles do not change mid-request.
 *
 * An actor that is not an active account of this IAM (unknown id, suspended,
 * revoked) may do nothing.
 */
final class UserPermissions implements ResetInterface
{
    /** @var array<string, UserAccess|null> */
    private array $resolved = [];

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleDefinitionRepositoryInterface $roles,
    ) {}

    public function of(Actor $actor): ?UserAccess
    {
        if ($actor->isSystem() || $actor->id === null) {
            return null;
        }

        return $this->resolved[$actor->id] ??= $this->resolve($actor->id);
    }

    public function reset(): void
    {
        $this->resolved = [];
    }

    private function resolve(string $accountId): ?UserAccess
    {
        try {
            $user = $this->users->findById(UserId::fromString($accountId));
        } catch (\InvalidArgumentException) {
            return null;
        }

        if ($user === null || !$user->status->canLogin()) {
            return null;
        }

        $superAdmin = false;
        $permissions = PermissionSet::none();
        foreach ($user->roles as $role) {
            $definition = $this->roles->findByRole($role);
            if ($definition === null) {
                continue;
            }
            $superAdmin = $superAdmin || $definition->system;
            $permissions = $permissions->merge($definition->permissions);
        }

        return new UserAccess($superAdmin, $permissions);
    }
}
