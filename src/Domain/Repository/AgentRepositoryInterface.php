<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Repository;

use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;

/**
 * @extends RepositoryInterface<Agent>
 */
interface AgentRepositoryInterface extends RepositoryInterface
{
    /**
     * Persists the aggregate and publishes the events it recorded, atomically.
     */
    public function save(Agent $agent): void;

    /**
     * How many agents carry this role, whatever their status — like
     * UserRepositoryInterface::countWithRole().
     *
     * @return int<0, max>
     */
    public function countWithRole(Role $role): int;

    /**
     * @return list<Agent> the agents that may act, by name
     */
    public function active(): array;
}
