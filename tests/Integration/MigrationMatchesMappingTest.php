<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Integration;

use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The bundle's migration and its ORM mapping describe the same schema — on
 * the platform under test, and whatever the configured table prefix.
 *
 * This is what makes a migration written with DBAL built-in types safe: if a
 * mapped type and its built-in counterpart ever drift apart, this fails.
 */
final class MigrationMatchesMappingTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function prefixes(): iterable
    {
        yield 'default prefix' => ['iam_'];
        yield 'custom prefix' => ['acme_'];
    }

    #[Test]
    #[DataProvider('prefixes')]
    public function the_migrated_schema_is_the_mapped_schema(string $prefix): void
    {
        $em = IamDatabase::migrated($prefix);

        $pending = (new SchemaTool($em))->getUpdateSchemaSql($em->getMetadataFactory()->getAllMetadata());

        self::assertSame([], $pending);
        self::assertTrue($em->getConnection()->createSchemaManager()->tablesExist([$prefix . 'user', $prefix . 'audit_log']));

        $em->getConnection()->close();
    }
}
