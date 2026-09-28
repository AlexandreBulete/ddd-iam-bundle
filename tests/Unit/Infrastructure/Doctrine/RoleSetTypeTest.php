<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Unit\Infrastructure\Doctrine;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\RoleSetType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\ConversionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoleSetTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{AbstractPlatform, string}>
     */
    public static function platforms(): iterable
    {
        yield 'PostgreSQL' => [new PostgreSQLPlatform(), 'JSONB'];
        yield 'MySQL' => [new MySQL80Platform(), 'JSON'];
        yield 'SQLite' => [new SQLitePlatform(), 'CLOB'];
    }

    #[Test]
    #[DataProvider('platforms')]
    public function it_declares_the_platform_json_type(AbstractPlatform $platform, string $expected): void
    {
        self::assertSame($expected, (new RoleSetType())->getSQLDeclaration([], $platform));
    }

    #[Test]
    public function it_round_trips_a_role_set(): void
    {
        $type = new RoleSetType();
        $platform = new PostgreSQLPlatform();
        $roles = RoleSet::fromNames(['admin', 'user']);

        $stored = $type->convertToDatabaseValue($roles, $platform);

        self::assertSame('["ROLE_ADMIN","ROLE_USER"]', $stored);
        self::assertTrue($roles->equals($type->convertToPHPValue($stored, $platform) ?? RoleSet::empty()));
    }

    #[Test]
    public function it_rejects_a_column_that_is_not_a_list_of_names(): void
    {
        $this->expectException(ConversionException::class);

        (new RoleSetType())->convertToPHPValue('{"admin": true}', new PostgreSQLPlatform());
    }
}
