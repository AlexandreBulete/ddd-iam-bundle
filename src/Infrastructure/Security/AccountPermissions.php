<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorKind;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Resolves an actor to what its account may do — once per request: every
 * command and query is checked, and roles do not change mid-request.
 *
 * A person and an agent are resolved the same way from their roles (ADR
 * 0011); only where the account is read from differs. An actor that is not an
 * active account of this IAM (unknown id, suspended, revoked) may do nothing.
 */
final class AccountPermissions implements ResetInterface
{
    /** @var array<string, AccountAccess|null> */
    private array $resolved = [];

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleDefinitionRepositoryInterface $roles,
        private readonly AgentRepositoryInterface $agents,
    ) {}

    public function of(Actor $actor): ?AccountAccess
    {
        if ($actor->isSystem() || $actor->id === null) {
            return null;
        }

        $key = $actor->kind->value . ':' . $actor->id;

        return array_key_exists($key, $this->resolved)
            ? $this->resolved[$key]
            : $this->resolved[$key] = $this->resolve($actor->kind, $actor->id);
    }

    public function reset(): void
    {
        $this->resolved = [];
    }

    private function resolve(ActorKind $kind, string $accountId): ?AccountAccess
    {
        $roles = $this->activeRoles($kind, $accountId);
        if ($roles === null) {
            return null;
        }

        $superAdmin = false;
        $permissions = PermissionSet::none();
        foreach ($roles as $role) {
            $definition = $this->roles->findByRole($role);
            if ($definition === null) {
                continue;
            }
            $superAdmin = $superAdmin || $definition->system;
            $permissions = $permissions->merge($definition->permissions);
        }

        return new AccountAccess($superAdmin, $permissions);
    }

    /**
     * The roles of an active account, null for anything else.
     */
    private function activeRoles(ActorKind $kind, string $accountId): ?RoleSet
    {
        try {
            $account = match ($kind) {
                ActorKind::User => $this->users->findById(UserId::fromString($accountId)),
                ActorKind::Agent => $this->agents->findById(AgentId::fromString($accountId)),
                ActorKind::System => null,
            };
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $account !== null && $account->status->isActive() ? $account->roles : null;
    }
}
