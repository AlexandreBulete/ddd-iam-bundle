<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine;

use AlexandreBulete\DddDoctrineBridge\DoctrineRepository;
use AlexandreBulete\DddFoundation\Domain\ValueObject\IdentifierVO;
use AlexandreBulete\DddIamBundle\Domain\Model\AuditLogEntry;
use AlexandreBulete\DddIamBundle\Domain\Repository\AuditLogEntryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends DoctrineRepository<AuditLogEntry>
 */
final class DoctrineAuditLogEntryRepository extends DoctrineRepository implements AuditLogEntryRepositoryInterface
{
    private const ENTITY_CLASS = AuditLogEntry::class;
    private const ALIAS = 'entry';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    public function findById(IdentifierVO $id): ?AuditLogEntry
    {
        return $this->em->find(self::ENTITY_CLASS, $id->value());
    }
}
