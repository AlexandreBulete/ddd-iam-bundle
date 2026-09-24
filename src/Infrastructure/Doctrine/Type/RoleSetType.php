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
        return $platform->getJsonTypeDeclarationSQL($column);
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

        try {
            /** @var list<string> $decoded */
            $decoded = json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ValueNotConvertible::new($value, self::NAME, $e->getMessage(), $e);
        }

        return RoleSet::fromNames($decoded);
    }
}
