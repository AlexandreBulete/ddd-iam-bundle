<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AuditLogEntryId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;

/**
 * Domain port — hands out identities for new aggregates.
 *
 * Injected rather than generated inside the model so that the Domain stays
 * deterministic: a test fixes the next identity instead of guessing it.
 */
interface IdentityGeneratorInterface
{
    public function nextUserId(): UserId;

    public function nextAuditLogEntryId(): AuditLogEntryId;

    public function nextRoleDefinitionId(): RoleDefinitionId;

    public function nextAgentId(): AgentId;

    public function nextApiTokenId(): ApiTokenId;
}
