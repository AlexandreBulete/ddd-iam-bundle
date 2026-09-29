<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * Agent accounts and their API tokens (ADR 0011).
 *
 * DBAL built-in types only — a migration is a snapshot (see
 * Version20260928120000).
 */
final class Version20261002130000 extends IamMigration
{
    public function getDescription(): string
    {
        return 'IAM: agent accounts and their API tokens.';
    }

    public function up(Schema $schema): void
    {
        $agent = $schema->createTable($this->table('agent'));
        $agent->addColumn('id', Types::GUID);
        $agent->addColumn('name', Types::STRING, ['length' => 100]);
        $agent->addColumn('description', Types::STRING, ['length' => 500, 'notnull' => false]);
        $agent->addColumn('roles', Types::JSONB);
        $agent->addColumn('status', Types::STRING, ['length' => 20]);
        $agent->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $agent->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $agent->addColumn('revoked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $agent->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $agent->addIndex(['status']);

        $token = $schema->createTable($this->table('api_token'));
        $token->addColumn('id', Types::GUID);
        $token->addColumn('agent_id', Types::GUID);
        $token->addColumn('label', Types::STRING, ['length' => 100]);
        $token->addColumn('digest', Types::STRING, ['length' => 64]);
        $token->addColumn('issued_at', Types::DATETIME_IMMUTABLE);
        $token->addColumn('expires_at', Types::DATETIME_IMMUTABLE);
        $token->addColumn('last_used_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $token->addColumn('revoked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $token->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $token->addIndex(['agent_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->table('api_token'));
        $schema->dropTable($this->table('agent'));
    }
}
