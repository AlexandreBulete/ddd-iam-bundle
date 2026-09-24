<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\ValueObject;

use AlexandreBulete\DddFoundation\Domain\ValueObject\StringVO;

/**
 * A single security role, in Symfony's `ROLE_*` form.
 *
 * Deliberately NOT a PHP enum. An enum is closed by construction: a project
 * could never add `ROLE_MODERATOR` without forking this bundle. The open set
 * lives in configuration (`iam.roles`) and is validated at runtime against
 * {@see \AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface} —
 * the Domain only enforces the *shape* of a role name here.
 *
 * Two spellings coexist on purpose:
 *   - the "name", lowercase and snake_case, is what a project writes in YAML
 *     and in translation keys (`moderator`, `iam.role.moderator`);
 *   - the "value", `ROLE_MODERATOR`, is what Symfony Security consumes.
 * {@see fromName()} and {@see name()} convert between the two so neither
 * spelling leaks into the other layer.
 */
final readonly class Role extends StringVO
{
    public const PREFIX = 'ROLE_';

    protected function validate(string $value): void
    {
        if (preg_match('/^ROLE_[A-Z0-9]+(?:_[A-Z0-9]+)*$/', $value) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid role "%s": expected the form ROLE_UPPER_SNAKE_CASE.',
                $value,
            ));
        }
    }

    /**
     * Builds a role from its configuration name: `moderator` → `ROLE_MODERATOR`.
     *
     * Accepts a name already prefixed, so `iam.roles` tolerates both spellings
     * rather than failing on a detail the author cannot guess.
     */
    public static function fromName(string $name): self
    {
        $normalized = strtoupper(trim($name));

        if (!str_starts_with($normalized, self::PREFIX)) {
            $normalized = self::PREFIX . $normalized;
        }

        return new self($normalized);
    }

    /**
     * The configuration name: `ROLE_MODERATOR` → `moderator`.
     */
    public function name(): string
    {
        return strtolower(substr($this->value, strlen(self::PREFIX)));
    }

    /**
     * Translation key for this role's label, e.g. `iam.role.moderator`.
     */
    public function labelKey(): string
    {
        return 'iam.role.' . $this->name();
    }
}
