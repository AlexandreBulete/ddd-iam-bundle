<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\ValueObject;

/**
 * The roles carried by a {@see \AlexandreBulete\DddIamBundle\Domain\Model\User}.
 *
 * A set, not a list: duplicates are collapsed and the order is normalised
 * (sorted), so two users granted the same roles in a different order compare
 * equal and produce identical JSON in the database — which keeps
 * {@see \AlexandreBulete\DddIamBundle\Domain\Model\User::changeRoles()}
 * genuinely idempotent instead of recording a no-op event on every save.
 *
 * Immutable: {@see with()} and {@see without()} return new instances.
 *
 * @implements \IteratorAggregate<int, Role>
 */
final readonly class RoleSet implements \Countable, \IteratorAggregate
{
    /** @var list<Role> */
    private array $roles;

    public function __construct(Role ...$roles)
    {
        $unique = [];
        foreach ($roles as $role) {
            $unique[$role->value()] = $role;
        }

        ksort($unique);

        $this->roles = array_values($unique);
    }

    /**
     * @param iterable<string> $values `ROLE_ADMIN` or `admin`, both accepted
     */
    public static function fromNames(iterable $values): self
    {
        $roles = [];
        foreach ($values as $value) {
            $roles[] = Role::fromName($value);
        }

        return new self(...$roles);
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @return list<string> the `ROLE_*` values Symfony Security consumes
     */
    public function toStrings(): array
    {
        return array_map(static fn (Role $role): string => $role->value(), $this->roles);
    }

    /**
     * @return list<string> the configuration names, for forms and YAML
     */
    public function toNames(): array
    {
        return array_map(static fn (Role $role): string => $role->name(), $this->roles);
    }

    public function contains(Role $role): bool
    {
        foreach ($this->roles as $existing) {
            if ($existing->equals($role)) {
                return true;
            }
        }

        return false;
    }

    public function with(Role $role): self
    {
        return new self(...[...$this->roles, $role]);
    }

    public function without(Role $role): self
    {
        return new self(...array_filter(
            $this->roles,
            static fn (Role $existing): bool => !$existing->equals($role),
        ));
    }

    public function union(self $other): self
    {
        return new self(...$this->roles, ...$other->roles);
    }

    public function equals(self $other): bool
    {
        return $this->toStrings() === $other->toStrings();
    }

    public function isEmpty(): bool
    {
        return $this->roles === [];
    }

    public function count(): int
    {
        return count($this->roles);
    }

    /**
     * @return \Traversable<int, Role>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->roles);
    }

    public function __toString(): string
    {
        return implode(', ', $this->toStrings());
    }
}
