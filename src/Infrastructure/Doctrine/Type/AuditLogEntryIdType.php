<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddDoctrineBridge\Type\GuidType;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AuditLogEntryId;

final class AuditLogEntryIdType extends GuidType
{
    public const NAME = 'iam_audit_log_entry_id';

    protected string $name = self::NAME;
    protected string $voClass = AuditLogEntryId::class;
}
