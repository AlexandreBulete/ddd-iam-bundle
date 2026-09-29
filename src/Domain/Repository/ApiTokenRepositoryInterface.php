<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Repository;

use AlexandreBulete\DddFoundation\Domain\Repository\RepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;

/**
 * @extends RepositoryInterface<ApiToken>
 */
interface ApiTokenRepositoryInterface extends RepositoryInterface
{
    public function save(ApiToken $token): void;

    /**
     * @return list<ApiToken> newest first
     */
    public function ofAgent(AgentId $agentId): array;
}
