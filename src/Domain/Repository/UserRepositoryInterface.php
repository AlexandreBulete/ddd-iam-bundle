<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Repository;

use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;

/**
 * @extends RepositoryInterface<User>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Persists the aggregate and publishes the events it recorded, atomically.
     */
    public function save(User $user): void;

    public function delete(User $user): void;

    public function findOneByEmail(string $email): ?User;

    /**
     * How many users carry this role — whatever their status: a suspended
     * account keeps its roles for when it is reactivated.
     *
     * @return int<0, max>
     */
    public function countWithRole(Role $role): int;

    /**
     * @return list<User>
     */
    public function findByEmailLike(string $search, ?int $limit = null): array;
}
