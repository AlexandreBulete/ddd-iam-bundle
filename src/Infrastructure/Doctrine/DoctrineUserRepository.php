<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine;

use AlexandreBulete\DddDoctrineBridge\DoctrineRepository;
use AlexandreBulete\DddFoundation\Domain\ValueObject\IdentifierVO;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\DomainEventPublisherInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends DoctrineRepository<User>
 */
final class DoctrineUserRepository extends DoctrineRepository implements UserRepositoryInterface
{
    private const ALIAS = 'iam_user';

    /**
     * @param class-string<User> $userClass configurable through `iam.user_class`
     */
    public function __construct(
        EntityManagerInterface $em,
        private readonly DomainEventPublisherInterface $eventPublisher,
        private readonly string $userClass = User::class,
    ) {
        parent::__construct($em, $userClass, self::ALIAS);
    }

    /**
     * Persists the aggregate, then publishes what it recorded — both inside a
     * single transaction.
     *
     * Order matters: the aggregate is flushed first so that a subscriber
     * reacting to the event (the audit logger, for one) sees a row that
     * already exists. And because the publish happens inside the transaction,
     * a failure in any subscriber rolls the account change back too — no event
     * ever describes a change that did not commit.
     */
    public function save(User $user): void
    {
        $this->em->wrapInTransaction(function () use ($user): void {
            $this->em->persist($user);
            $this->em->flush();

            $this->eventPublisher->publishAll($user->releaseEvents());
        });
    }

    /**
     * Hard delete. The back office does NOT call this — its delete button
     * revokes instead, to keep the audit trail meaningful. This exists for
     * GDPR erasure and for test fixtures.
     */
    public function delete(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }

    public function findById(IdentifierVO $id): ?User
    {
        return $this->em->find($this->userClass, $id->value());
    }

    public function findOneByEmail(string $email): ?User
    {
        $user = $this->query()
            ->andWhere(self::ALIAS . '.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        return $user instanceof User ? $user : null;
    }

    /**
     * @return list<User>
     */
    public function findByEmailLike(string $search, ?int $limit = null): array
    {
        /** @var list<User> */
        return $this->query()
            ->andWhere('LOWER(' . self::ALIAS . '.email) LIKE LOWER(:email)')
            ->setParameter('email', '%' . trim($search) . '%')
            ->setMaxResults($limit)
            ->orderBy(self::ALIAS . '.email', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
