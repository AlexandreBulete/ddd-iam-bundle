<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Integration;

use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Domain\Model\AuditLogEntry;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Listener\TablePrefixListener;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Migrations\Version20260928120000;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Migrations\Version20261001120000;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Migrations\Version20261002130000;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\AgentIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\ApiTokenDigestType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\ApiTokenIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\AuditLogEntryIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\EmailType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\PasswordType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\PermissionSetType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\RoleDefinitionIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\RoleSetType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\RoleType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\UserIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\UserStatusType;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\ORMSetup;
use Psr\Log\NullLogger;

/**
 * A real database holding the IAM tables, built by the bundle's own migration.
 *
 * DDD_TEST_DATABASE_URL picks the platform (CI runs PostgreSQL, MySQL and
 * SQLite); SQLite in memory otherwise. The connection only sees tables carrying
 * the prefix under test, so a shared test database with other packages'
 * tables does not leak into the comparisons.
 */
final class IamDatabase
{
    /** Mirrors DddIamBundle::TABLES. */
    private const TABLES = [
        User::class => 'user',
        AuditLogEntry::class => 'audit_log',
        RoleDefinition::class => 'role',
        Agent::class => 'agent',
        ApiToken::class => 'api_token',
    ];

    public static function migrated(string $prefix): EntityManagerInterface
    {
        self::registerTypes();

        $config = ORMSetup::createConfiguration(true);
        $config->setMetadataDriverImpl(new SimplifiedXmlDriver([
            dirname(__DIR__, 2) . '/src/Infrastructure/Doctrine/Mapping' => 'AlexandreBulete\DddIamBundle\Domain\Model',
        ]));
        $config->enableNativeLazyObjects(true);
        $config->setSchemaAssetsFilter(static fn (string $asset): bool => str_starts_with($asset, $prefix));

        $connection = self::connect($config);
        self::dropTables($connection);
        self::runMigration($connection, $prefix);

        $events = new EventManager();
        $events->addEventListener(Events::loadClassMetadata, new TablePrefixListener($prefix, self::TABLES));

        return new EntityManager($connection, $config, $events);
    }

    private static function connect(Configuration $config): Connection
    {
        $url = getenv('DDD_TEST_DATABASE_URL');

        $params = (new DsnParser([
            'pdo-sqlite' => 'pdo_sqlite',
            'postgresql' => 'pdo_pgsql',
            'mysql' => 'pdo_mysql',
        ]))->parse(is_string($url) && $url !== '' ? $url : 'pdo-sqlite:///:memory:');

        return DriverManager::getConnection($params, $config);
    }

    private static function dropTables(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();
        foreach ($schemaManager->listTableNames() as $table) {
            $schemaManager->dropTable($table);
        }
    }

    /**
     * What `doctrine:migrations:migrate` does for Schema API migrations, in
     * order: diff the introspected schema against the one up() describes,
     * then postUp() (seed data).
     */
    private static function runMigration(Connection $connection, string $prefix): void
    {
        foreach ([Version20260928120000::class, Version20261001120000::class, Version20261002130000::class] as $class) {
            $migration = new $class($connection, new NullLogger(), $prefix);
            $schemaManager = $connection->createSchemaManager();
            $from = $schemaManager->introspectSchema();
            $to = clone $from;

            $migration->up($to);
            foreach ($connection->getDatabasePlatform()->getAlterSchemaSQL($schemaManager->createComparator()->compareSchemas($from, $to)) as $sql) {
                $connection->executeStatement($sql);
            }
            $migration->postUp($to);
        }
    }

    private static function registerTypes(): void
    {
        foreach ([
            UserIdType::NAME => UserIdType::class,
            EmailType::NAME => EmailType::class,
            PasswordType::NAME => PasswordType::class,
            UserStatusType::NAME => UserStatusType::class,
            RoleSetType::NAME => RoleSetType::class,
            AuditLogEntryIdType::NAME => AuditLogEntryIdType::class,
            RoleType::NAME => RoleType::class,
            RoleDefinitionIdType::NAME => RoleDefinitionIdType::class,
            PermissionSetType::NAME => PermissionSetType::class,
            AgentIdType::NAME => AgentIdType::class,
            ApiTokenIdType::NAME => ApiTokenIdType::class,
            ApiTokenDigestType::NAME => ApiTokenDigestType::class,
        ] as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }
    }
}
