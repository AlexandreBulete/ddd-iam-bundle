<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * Creates the IAM tables.
 *
 * Schema API rather than raw SQL: the DDL follows the project's platform
 * (JSONB on PostgreSQL, JSON on MySQL, …).
 *
 * DBAL's built-in types only, never the bundle's own (RoleSetType, UserIdType…):
 * a migration is a snapshot. Were it to reference live code, changing a type
 * tomorrow would silently change what this 2026 migration creates, and two
 * databases migrated a year apart would differ. Each built-in below produces
 * the same column as the mapped type does today — the integration tests check
 * it against the mapping on PostgreSQL, MySQL and SQLite.
 *
 * Index names are generated, never written: they derive from the prefixed
 * table name exactly as the ORM derives them from the mapping, and stay unique
 * whatever `iam.table_prefix` is (PostgreSQL index names are schema-wide).
 */
final class Version20260928120000 extends IamMigration
{
    public function getDescription(): string
    {
        return 'IAM: users and audit log tables.';
    }

    public function up(Schema $schema): void
    {
        $user = $schema->createTable($this->table('user'));
        $user->addColumn('id', Types::GUID);
        $user->addColumn('email', Types::STRING, ['length' => 255]);
        $user->addColumn('password', Types::STRING, ['length' => 255]);
        $user->addColumn('first_name', Types::STRING, ['length' => 100, 'notnull' => false]);
        $user->addColumn('last_name', Types::STRING, ['length' => 100, 'notnull' => false]);
        $user->addColumn('roles', Types::JSONB);
        $user->addColumn('status', Types::STRING, ['length' => 20]);
        $user->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $user->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $user->addColumn('revoked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $user->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $user->addUniqueIndex(['email']);
        $user->addIndex(['status']);

        $audit = $schema->createTable($this->table('audit_log'));
        $audit->addColumn('id', Types::GUID);
        $audit->addColumn('event_type', Types::STRING, ['length' => 255]);
        $audit->addColumn('user_id', Types::STRING, ['length' => 40]);
        $audit->addColumn('payload', Types::TEXT);
        $audit->addColumn('occurred_at', Types::DATETIME_IMMUTABLE);
        $audit->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $audit->addIndex(['user_id']);
        $audit->addIndex(['event_type']);
        $audit->addIndex(['occurred_at']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->table('audit_log'));
        $schema->dropTable($this->table('user'));
    }
}
