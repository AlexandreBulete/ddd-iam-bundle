<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;

/**
 * Base for the migrations this bundle ships.
 *
 * The bundle owns its schema: installing it must be enough to get the tables,
 * without a `migrations:diff` in the host project that would copy IAM DDL into
 * the application's history. Its migrations are therefore registered as
 * services (DoctrineMigrationsBundle `enable_service_migrations`), which is the
 * only way for them to receive `iam.table_prefix` — a static migration class
 * cannot know which prefix the project configured.
 */
abstract class IamMigration extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly string $tablePrefix,
    ) {
        parent::__construct($connection, $logger);
    }

    /**
     * @param 'user'|'audit_log'|'role' $name unprefixed table name, as in DddIamBundle::TABLES
     */
    protected function table(string $name): string
    {
        return $this->tablePrefix . $name;
    }
}
