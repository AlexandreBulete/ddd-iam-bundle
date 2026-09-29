<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\ValueObject;

/**
 * The permissions a role grants: use cases (`client.find_clients`) and entry
 * points (`backoffice.access`), in dotted snake_case (ADR 0008).
 *
 * A set — sorted, without duplicates — so that two sets with the same
 * permissions are equal, whatever the order they were ticked in.
 *
 * @implements \IteratorAggregate<int, string>
 */
final readonly class PermissionSet implements \Countable, \IteratorAggregate
{
    private const FORMAT = '/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/';

    /** @var list<string> */
    private array $permissions;

    public function __construct(string ...$permissions)
    {
        foreach ($permissions as $permission) {
            if (preg_match(self::FORMAT, $permission) !== 1) {
                throw new \InvalidArgumentException(sprintf('Invalid permission "%s": expected dotted snake_case, like "client.read".', $permission));
            }
        }

        $unique = array_values(array_unique($permissions));
        sort($unique);

        $this->permissions = $unique;
    }

    /**
     * @param iterable<string> $permissions
     */
    public static function of(iterable $permissions): self
    {
        return new self(...(is_array($permissions) ? array_values($permissions) : iterator_to_array($permissions, false)));
    }

    public static function none(): self
    {
        return new self();
    }

    public function contains(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function containsAll(self $other): bool
    {
        return array_diff($other->permissions, $this->permissions) === [];
    }

    public function merge(self $other): self
    {
        return new self(...$this->permissions, ...$other->permissions);
    }

    public function equals(self $other): bool
    {
        return $this->permissions === $other->permissions;
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->permissions;
    }

    public function count(): int
    {
        return count($this->permissions);
    }

    /**
     * @return \Iterator<int, string>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->permissions);
    }
}
