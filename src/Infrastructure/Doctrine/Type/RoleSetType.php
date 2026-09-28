<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Maps {@see RoleSet} onto a JSON column.
 *
 * JSON rather than a join table: a role set is read on every single request
 * (the firewall refreshes the user from the session), it is always loaded and
 * written whole, and it never needs to be queried from the role side. A join
 * table would buy referential integrity against a `iam_role` table that does
 * not exist — the catalogue lives in configuration, not in the database.
 *
 * The stored shape is a plain JSON array of `ROLE_*` strings, so the column
 * stays readable in psql and greppable in a dump.
 *
 * JSONB on PostgreSQL: the binary form is the one PostgreSQL recommends —
 * parsed once on write, comparable and indexable, where `json` is stored as
 * text and has no equality operator. Other platforms get their JSON type.
 */
final class RoleSetType extends Type
{
    public const NAME = 'iam_roles';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonbTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof RoleSet) {
            throw new \InvalidArgumentException(sprintf(
                'Expected %s, got %s.',
                RoleSet::class,
                get_debug_type($value),
            ));
        }

        return json_encode($value->toStrings(), JSON_THROW_ON_ERROR);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?RoleSet
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof RoleSet) {
            return $value;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        if (!is_string($value)) {
            throw ValueNotConvertible::new($value, self::NAME, 'expected a JSON string');
        }

        try {
            $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ValueNotConvertible::new($value, self::NAME, $e->getMessage(), $e);
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw ValueNotConvertible::new($value, self::NAME, 'expected a JSON array of role names');
        }

        $names = [];
        foreach ($decoded as $name) {
            if (!is_string($name)) {
                throw ValueNotConvertible::new($value, self::NAME, 'role names must be strings');
            }
            $names[] = $name;
        }

        return RoleSet::fromNames($names);
    }
}
