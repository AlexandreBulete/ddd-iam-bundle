<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Repository;

use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\User;

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
     * @return list<User>
     */
    public function findByEmailLike(string $search, ?int $limit = null): array;
}
