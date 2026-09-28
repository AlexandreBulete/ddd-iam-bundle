<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Integration;

use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Password;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineUserRepository;
use AlexandreBulete\DddIamBundle\Tests\Integration\Fixture\NullEventPublisher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DoctrineUserRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        $this->em = IamDatabase::migrated('iam_');
        $this->repository = new DoctrineUserRepository($this->em, new NullEventPublisher());

        foreach (['carol', 'alice', 'bob'] as $name) {
            $this->repository->save(User::create(
                id: UserId::generate(),
                email: Email::fromString($name . '@example.com'),
                password: Password::fromString('$2y$13$' . str_repeat('x', 53)),
                roles: RoleSet::fromNames(['admin', 'user']),
                createdAt: new \DateTimeImmutable('2026-01-01'),
            ));
        }
        $this->em->clear();
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
    }

    /**
     * The back-office user list, as the grid asks for it. It used to fail on
     * PostgreSQL: roles live in a JSON column, and the paginated query was a
     * SELECT DISTINCT over the whole row.
     */
    #[Test]
    public function it_lists_users_page_by_page(): void
    {
        $page = $this->repository->orderBy('email', 'asc')->withPagination(1, 2);

        $emails = [];
        foreach ($page as $user) {
            $emails[] = $user->email->value();
        }

        self::assertSame(['alice@example.com', 'bob@example.com'], $emails);
        self::assertCount(3, $page);
    }

    #[Test]
    public function it_hydrates_roles_and_identity_back(): void
    {
        $user = $this->repository->findOneByEmail('alice@example.com');

        self::assertNotNull($user);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->roles->toStrings());
        self::assertTrue($user->status->isActive());
    }
}
