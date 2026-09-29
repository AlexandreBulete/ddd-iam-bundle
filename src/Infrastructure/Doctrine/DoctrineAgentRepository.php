<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine;

use AlexandreBulete\DddDoctrineBridge\DoctrineRepository;
use AlexandreBulete\DddFoundation\Domain\ValueObject\IdentifierVO;
use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\DomainEventPublisherInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends DoctrineRepository<Agent>
 */
final class DoctrineAgentRepository extends DoctrineRepository implements AgentRepositoryInterface
{
    private const ALIAS = 'iam_agent';

    public function __construct(
        EntityManagerInterface $em,
        private readonly DomainEventPublisherInterface $eventPublisher,
    ) {
        parent::__construct($em, Agent::class, self::ALIAS);
    }

    /**
     * Same contract as DoctrineUserRepository::save(): flushed, then its
     * events published, in one transaction.
     */
    public function save(Agent $agent): void
    {
        $this->em->wrapInTransaction(function () use ($agent): void {
            $this->em->persist($agent);
            $this->em->flush();
            $this->eventPublisher->publishAll($agent->releaseEvents());
        });
    }

    public function findById(IdentifierVO $id): ?Agent
    {
        return $this->em->find(Agent::class, $id->value());
    }

    /**
     * Scanned in PHP, like DoctrineUserRepository::countWithRole(): matching
     * inside a JSON column differs on every platform, and this runs only when
     * a role is removed.
     */
    public function countWithRole(Role $role): int
    {
        $count = 0;
        foreach ($this->query()->getQuery()->toIterable() as $agent) {
            if ($agent instanceof Agent && $agent->roles->contains($role)) {
                ++$count;
            }
        }

        return $count;
    }

    public function active(): array
    {
        /** @var list<Agent> */
        return $this->query()
            ->andWhere(self::ALIAS . '.status = :active')
            ->setParameter('active', UserStatusEnum::ACTIVE->value)
            ->orderBy(self::ALIAS . '.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
