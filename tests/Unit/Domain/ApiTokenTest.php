<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Unit\Domain;

use AlexandreBulete\DddIamBundle\Domain\Event\ApiTokenIssued;
use AlexandreBulete\DddIamBundle\Domain\Event\ApiTokenRevoked;
use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenDigest;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiTokenTest extends TestCase
{
    private const SECRET = 'a-secret-of-at-least-thirty-two-characters';

    #[Test]
    public function it_opens_with_its_secret_only_until_it_expires(): void
    {
        $token = self::token(expiresAt: '2026-04-01');

        self::assertTrue($token->authenticates(self::SECRET, new \DateTimeImmutable('2026-03-31 23:59')));
        self::assertFalse($token->authenticates('another-secret-of-thirty-two-characters', new \DateTimeImmutable('2026-02-01')));
        self::assertFalse($token->authenticates(self::SECRET, new \DateTimeImmutable('2026-04-01')));
    }

    #[Test]
    public function a_revoked_token_opens_nothing(): void
    {
        $token = self::token();
        $token->releaseEvents();

        $token->revoke(new \DateTimeImmutable('2026-01-02'));
        $token->revoke(new \DateTimeImmutable('2026-01-03'));

        self::assertFalse($token->authenticates(self::SECRET, new \DateTimeImmutable('2026-01-04')));
        self::assertEquals(new \DateTimeImmutable('2026-01-02'), $token->revokedAt, 'revoking twice changes nothing');
        self::assertCount(1, $events = $token->releaseEvents());
        self::assertInstanceOf(ApiTokenRevoked::class, $events[0]);
    }

    #[Test]
    public function it_never_outlives_a_year(): void
    {
        self::token(expiresAt: '2027-01-01');

        $this->expectException(\DomainException::class);
        self::token(expiresAt: '2027-01-01 00:00:01');
    }

    #[Test]
    public function it_must_expire_after_it_is_issued(): void
    {
        $this->expectException(\DomainException::class);
        self::token(expiresAt: '2026-01-01');
    }

    #[Test]
    public function issuing_it_records_no_secret(): void
    {
        $events = self::token()->releaseEvents();

        self::assertInstanceOf(ApiTokenIssued::class, $events[0]);
        self::assertStringNotContainsString(self::SECRET, serialize($events[0]));
    }

    #[Test]
    public function last_use_is_tracked_every_five_minutes_at_most(): void
    {
        $token = self::token();

        self::assertTrue($token->markUsed(new \DateTimeImmutable('2026-01-02 10:00')));
        self::assertFalse($token->markUsed(new \DateTimeImmutable('2026-01-02 10:04')));
        self::assertTrue($token->markUsed(new \DateTimeImmutable('2026-01-02 10:05')));
        self::assertEquals(new \DateTimeImmutable('2026-01-02 10:05'), $token->lastUsedAt);
    }

    private static function token(string $expiresAt = '2026-04-01'): ApiToken
    {
        return ApiToken::issue(
            ApiTokenId::generate(),
            AgentId::generate(),
            'CI lapsa',
            ApiTokenDigest::of(self::SECRET),
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable($expiresAt),
        );
    }
}
