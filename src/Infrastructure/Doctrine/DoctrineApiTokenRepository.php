<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine;

use AlexandreBulete\DddDoctrineBridge\DoctrineRepository;
use AlexandreBulete\DddFoundation\Domain\ValueObject\IdentifierVO;
use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Domain\Repository\ApiTokenRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\DomainEventPublisherInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends DoctrineRepository<ApiToken>
 */
final class DoctrineApiTokenRepository extends DoctrineRepository implements ApiTokenRepositoryInterface
{
    private const ALIAS = 'iam_api_token';

    public function __construct(
        EntityManagerInterface $em,
        private readonly DomainEventPublisherInterface $eventPublisher,
    ) {
        parent::__construct($em, ApiToken::class, self::ALIAS);
    }

    public function save(ApiToken $token): void
    {
        $this->em->wrapInTransaction(function () use ($token): void {
            $this->em->persist($token);
            $this->em->flush();
            $this->eventPublisher->publishAll($token->releaseEvents());
        });
    }

    public function findById(IdentifierVO $id): ?ApiToken
    {
        return $this->em->find(ApiToken::class, $id->value());
    }

    public function ofAgent(AgentId $agentId): array
    {
        /** @var list<ApiToken> */
        return $this->query()
            ->andWhere(self::ALIAS . '.agentId = :agent')
            ->setParameter('agent', $agentId->toRfc4122())
            ->orderBy(self::ALIAS . '.issuedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
