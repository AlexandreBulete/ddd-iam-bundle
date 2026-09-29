<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine;

use AlexandreBulete\DddDoctrineBridge\DoctrineRepository;
use AlexandreBulete\DddFoundation\Domain\ValueObject\IdentifierVO;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\DomainEventPublisherInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends DoctrineRepository<RoleDefinition>
 */
final class DoctrineRoleDefinitionRepository extends DoctrineRepository implements RoleDefinitionRepositoryInterface
{
    private const ALIAS = 'role_definition';

    public function __construct(
        EntityManagerInterface $em,
        private readonly DomainEventPublisherInterface $eventPublisher,
    ) {
        parent::__construct($em, RoleDefinition::class, self::ALIAS);
    }

    /**
     * Same contract as DoctrineUserRepository::save(): flushed, then its
     * events published, in one transaction.
     */
    public function save(RoleDefinition $definition): void
    {
        $this->em->wrapInTransaction(function () use ($definition): void {
            $this->em->persist($definition);
            $this->em->flush();

            $this->eventPublisher->publishAll($definition->releaseEvents());
        });
    }

    public function remove(RoleDefinition $definition): void
    {
        $this->em->wrapInTransaction(function () use ($definition): void {
            $this->em->remove($definition);
            $this->em->flush();

            $this->eventPublisher->publishAll($definition->releaseEvents());
        });
    }

    public function findById(IdentifierVO $id): ?RoleDefinition
    {
        return $this->em->find(RoleDefinition::class, $id->value());
    }

    public function findByRole(Role $role): ?RoleDefinition
    {
        $definition = $this->query()
            ->andWhere(self::ALIAS . '.role = :role')
            ->setParameter('role', $role->value())
            ->getQuery()
            ->getOneOrNullResult();

        return $definition instanceof RoleDefinition ? $definition : null;
    }

    public function all(): array
    {
        /** @var list<RoleDefinition> */
        return $this->query()
            ->orderBy(self::ALIAS . '.system', 'DESC')
            ->addOrderBy(self::ALIAS . '.label', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
