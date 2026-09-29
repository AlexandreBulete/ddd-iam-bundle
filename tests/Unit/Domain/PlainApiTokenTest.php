<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Unit\Domain;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainApiToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlainApiTokenTest extends TestCase
{
    private const SECRET = 'x7Kq_under-scores_and-dashes_are-url-safe';

    #[Test]
    public function what_is_issued_is_what_is_read_back(): void
    {
        $issued = PlainApiToken::compose('pilot', ApiTokenId::generate(), self::SECRET);

        $read = PlainApiToken::parse('pilot', $issued->toString());

        self::assertNotNull($read);
        self::assertTrue($issued->id->equals($read->id));
        self::assertSame(self::SECRET, $read->secret, 'underscores in the secret are not separators');
        self::assertTrue($read->digest()->matches(self::SECRET));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notOurs(): iterable
    {
        $id = (string) ApiTokenId::generate();

        yield 'another prefix' => ["other_{$id}_" . self::SECRET];
        yield 'no id' => ['pilot_' . self::SECRET];
        yield 'not an id' => ['pilot_not-an-id_' . self::SECRET];
        yield 'short secret' => ["pilot_{$id}_short"];
        yield 'empty' => [''];
    }

    #[Test]
    #[DataProvider('notOurs')]
    public function anything_else_is_not_a_token(string $presented): void
    {
        self::assertNull(PlainApiToken::parse('pilot', $presented));
    }

    #[Test]
    public function its_secret_does_not_show_in_a_dump(): void
    {
        $token = PlainApiToken::compose('pilot', ApiTokenId::generate(), self::SECRET);

        self::assertStringNotContainsString(self::SECRET, print_r($token, true));
    }

    #[Test]
    public function a_prefix_is_a_short_lowercase_word(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PlainApiToken::compose('Pilot_', ApiTokenId::generate(), self::SECRET);
    }
}
