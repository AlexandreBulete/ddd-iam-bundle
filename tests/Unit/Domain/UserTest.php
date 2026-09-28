<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Unit\Domain;

use AlexandreBulete\DddIamBundle\Domain\Event\UserCreated;
use AlexandreBulete\DddIamBundle\Domain\Event\UserRenamed;
use AlexandreBulete\DddIamBundle\Domain\Event\UserRevoked;
use AlexandreBulete\DddIamBundle\Domain\Event\UserRolesChanged;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Password;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private const ID = '01J8ZQ7X3M5V9K2R4T6Y8B0C1D';

    #[Test]
    public function creation_takes_its_identity_and_time_from_the_caller(): void
    {
        $user = self::user();

        self::assertTrue(UserId::fromString(self::ID)->equals($user->id));
        self::assertEquals(new \DateTimeImmutable('2026-01-01 10:00'), $user->createdAt);
        self::assertNull($user->updatedAt);
        self::assertTrue($user->status->isActive());

        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserCreated::class, $events[0]);
    }

    #[Test]
    public function a_change_is_stamped_with_the_given_time(): void
    {
        $user = self::user();
        $user->releaseEvents();

        $user->rename('Ada', 'Lovelace', new \DateTimeImmutable('2026-02-01 09:30'));

        self::assertSame('Ada Lovelace', $user->getFullName());
        self::assertEquals(new \DateTimeImmutable('2026-02-01 09:30'), $user->updatedAt);
        self::assertInstanceOf(UserRenamed::class, $user->releaseEvents()[0]);
    }

    #[Test]
    public function an_unchanged_value_records_nothing(): void
    {
        $user = self::user();
        $user->releaseEvents();

        $user->changeRoles(RoleSet::fromNames(['admin']), new \DateTimeImmutable('2026-02-01'));

        self::assertSame([], $user->releaseEvents());
        self::assertNull($user->updatedAt);
    }

    #[Test]
    public function a_role_change_carries_before_and_after(): void
    {
        $user = self::user();
        $user->releaseEvents();

        $user->changeRoles(RoleSet::fromNames(['user']), new \DateTimeImmutable('2026-02-01'));

        $event = $user->releaseEvents()[0];
        self::assertInstanceOf(UserRolesChanged::class, $event);
        self::assertSame(['ROLE_USER'], $event->roles);
        self::assertSame(['ROLE_ADMIN'], $event->previousRoles);
    }

    #[Test]
    public function revoking_is_terminal(): void
    {
        $user = self::user();
        $user->releaseEvents();
        $at = new \DateTimeImmutable('2026-03-01 12:00');

        $user->revoke($at);
        $user->revoke(new \DateTimeImmutable('2026-04-01'));

        self::assertEquals($at, $user->revokedAt, 'a second revoke changes nothing');
        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRevoked::class, $events[0]);

        $this->expectException(\DomainException::class);
        $user->rename('Too', 'Late', new \DateTimeImmutable('2026-05-01'));
    }

    #[Test]
    public function suspension_round_trips(): void
    {
        $user = self::user();

        $user->suspend(new \DateTimeImmutable('2026-02-01'));
        self::assertTrue($user->status->isSuspended());

        $user->reactivate(new \DateTimeImmutable('2026-02-02'));
        self::assertTrue($user->status->isActive());
        self::assertEquals(new \DateTimeImmutable('2026-02-02'), $user->updatedAt);
    }

    private static function user(): User
    {
        return User::create(
            id: UserId::fromString(self::ID),
            email: Email::fromString('ada@example.com'),
            password: Password::fromString('$2y$13$hashedhashedhashedhashedhashedhashedhashedhashedhashe'),
            roles: RoleSet::fromNames(['admin']),
            createdAt: new \DateTimeImmutable('2026-01-01 10:00'),
        );
    }
}
