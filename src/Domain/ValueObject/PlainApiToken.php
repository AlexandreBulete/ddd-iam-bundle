<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\ValueObject;

/**
 * A token as its agent presents it: `<prefix>_<id>_<secret>` (ADR 0011).
 *
 * - the prefix makes a leaked token recognisable by secret scanners;
 * - the id finds the one row to check, without scanning;
 * - the secret is what is verified, against its digest.
 *
 * Exists only between issuance and the agent's configuration, and during the
 * request it authenticates: never stored, never logged.
 */
final readonly class PlainApiToken
{
    private const PREFIX = '/^[a-z][a-z0-9]{1,15}$/';

    private function __construct(
        public string $prefix,
        public ApiTokenId $id,
        public string $secret,
    ) {}

    public static function compose(string $prefix, ApiTokenId $id, string $secret): self
    {
        if (preg_match(self::PREFIX, $prefix) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid token prefix "%s": lowercase letters and digits, 2 to 16.', $prefix));
        }
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('A token secret is at least 32 characters long.');
        }

        return new self($prefix, $id, $secret);
    }

    /**
     * Null for anything that is not a token of this prefix: whoever presents
     * garbage learns nothing from how it is refused.
     */
    public static function parse(string $prefix, string $presented): ?self
    {
        $parts = explode('_', $presented, 3);
        if (count($parts) !== 3 || $parts[0] !== $prefix) {
            return null;
        }

        try {
            return self::compose($prefix, ApiTokenId::fromString($parts[1]), $parts[2]);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    public function toString(): string
    {
        return $this->prefix . '_' . (string) $this->id . '_' . $this->secret;
    }

    public function digest(): ApiTokenDigest
    {
        return ApiTokenDigest::of($this->secret);
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['prefix' => $this->prefix, 'id' => (string) $this->id, 'secret' => '***'];
    }
}
