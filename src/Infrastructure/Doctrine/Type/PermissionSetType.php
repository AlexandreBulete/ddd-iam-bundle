<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * A {@see PermissionSet} as a JSON array of permission ids — JSONB on
 * PostgreSQL, the platform's JSON elsewhere (same reasoning as RoleSetType).
 */
final class PermissionSetType extends Type
{
    public const NAME = 'iam_permissions';

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

        if (!$value instanceof PermissionSet) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', PermissionSet::class, get_debug_type($value)));
        }

        return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?PermissionSet
    {
        if ($value === null || $value instanceof PermissionSet) {
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
            throw ValueNotConvertible::new($value, self::NAME, 'expected a JSON array of permission ids');
        }

        $permissions = [];
        foreach ($decoded as $permission) {
            if (!is_string($permission)) {
                throw ValueNotConvertible::new($value, self::NAME, 'permission ids must be strings');
            }
            $permissions[] = $permission;
        }

        return PermissionSet::of($permissions);
    }
}
