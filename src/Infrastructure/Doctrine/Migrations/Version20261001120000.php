<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Ulid;

/**
 * Roles become data (ADR 0008): the role definitions table, and the one
 * system role, super_admin, which grants every permission.
 *
 * DBAL built-in types only — a migration is a snapshot (see
 * Version20260928120000).
 */
final class Version20261001120000 extends IamMigration
{
    public function getDescription(): string
    {
        return 'IAM: role definitions, with the super_admin system role.';
    }

    public function up(Schema $schema): void
    {
        $roles = $schema->createTable($this->table('role'));
        $roles->addColumn('id', Types::GUID);
        $roles->addColumn('role', Types::STRING, ['length' => 64]);
        $roles->addColumn('label', Types::STRING, ['length' => 100]);
        $roles->addColumn('permissions', Types::JSONB);
        $roles->addColumn('is_system', Types::BOOLEAN); // not `system`: reserved in MySQL
        $roles->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $roles->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $roles->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $roles->addUniqueIndex(['role']);
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->insert($this->table('role'), [
            'id' => (new Ulid())->toRfc4122(),
            // Literal, not RoleDefinition::SUPER_ADMIN: a migration is a snapshot.
            'role' => 'ROLE_SUPER_ADMIN',
            'label' => 'Super admin',
            'permissions' => [],
            'is_system' => true,
            'created_at' => new \DateTimeImmutable(),
        ], [
            'permissions' => Types::JSONB,
            'is_system' => Types::BOOLEAN,
            'created_at' => Types::DATETIME_IMMUTABLE,
        ]);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->table('role'));
    }
}
