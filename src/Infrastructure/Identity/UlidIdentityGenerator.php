<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Identity;

use AlexandreBulete\DddIamBundle\Domain\Service\IdentityGeneratorInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AuditLogEntryId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;

/**
 * ULIDs: sortable by creation time, which keeps inserts append-friendly on the
 * primary key index and makes ids readable in chronological order.
 */
final readonly class UlidIdentityGenerator implements IdentityGeneratorInterface
{
    public function nextUserId(): UserId
    {
        return UserId::generate();
    }

    public function nextAuditLogEntryId(): AuditLogEntryId
    {
        return AuditLogEntryId::generate();
    }

    public function nextRoleDefinitionId(): RoleDefinitionId
    {
        return RoleDefinitionId::generate();
    }

    public function nextAgentId(): AgentId
    {
        return AgentId::generate();
    }

    public function nextApiTokenId(): ApiTokenId
    {
        return ApiTokenId::generate();
    }
}
