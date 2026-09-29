<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Model;

use AlexandreBulete\DddFoundation\Domain\Model\RecordsEvents;
use AlexandreBulete\DddIamBundle\Domain\Event\ApiTokenIssued;
use AlexandreBulete\DddIamBundle\Domain\Event\ApiTokenRevoked;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenDigest;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenId;

/**
 * A credential of an {@see Agent} (ADR 0011): opaque, stored as a digest,
 * always expiring, revocable on its own.
 *
 * An agent may hold several: rotating a token is issuing the next one,
 * deploying it, then revoking the previous one — without a gap.
 */
final class ApiToken
{
    use RecordsEvents;

    /** Longest a token may live: a forgotten token must die on its own. */
    public const MAX_LIFETIME = 'P1Y';

    /** How stale "last used" may get: tracking every request is a write per request. */
    private const USE_TRACKING = 'PT5M';

    private function __construct(
        private(set) ApiTokenId $id,
        private(set) AgentId $agentId,
        private(set) string $label,
        private(set) ApiTokenDigest $digest,
        private(set) \DateTimeImmutable $issuedAt,
        private(set) \DateTimeImmutable $expiresAt,
        private(set) ?\DateTimeImmutable $lastUsedAt,
        private(set) ?\DateTimeImmutable $revokedAt,
    ) {}

    public static function issue(
        ApiTokenId $id,
        AgentId $agentId,
        string $label,
        ApiTokenDigest $digest,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
    ): self {
        $label = trim($label);
        if ($label === '' || mb_strlen($label) > 100) {
            throw new \InvalidArgumentException('A token label is between 1 and 100 characters.');
        }
        if ($expiresAt <= $issuedAt) {
            throw new \DomainException('A token must expire after it is issued.');
        }
        if ($expiresAt > $issuedAt->add(new \DateInterval(self::MAX_LIFETIME))) {
            throw new \DomainException('A token lives one year at most.');
        }

        $token = new self($id, $agentId, $label, $digest, $issuedAt, $expiresAt, null, null);
        $token->recordEvent(new ApiTokenIssued((string) $id, (string) $agentId, $label, $expiresAt));

        return $token;
    }

    /**
     * Whether a presented secret opens this token at that moment. The agent's
     * own status is its aggregate's to tell.
     */
    public function authenticates(string $secret, \DateTimeImmutable $at): bool
    {
        return $this->isUsableAt($at) && $this->digest->matches($secret);
    }

    public function isUsableAt(\DateTimeImmutable $at): bool
    {
        return $this->revokedAt === null && $at < $this->expiresAt;
    }

    /**
     * @return bool whether anything changed — worth saving
     */
    public function markUsed(\DateTimeImmutable $at): bool
    {
        if ($this->lastUsedAt !== null && $at < $this->lastUsedAt->add(new \DateInterval(self::USE_TRACKING))) {
            return false;
        }

        $this->lastUsedAt = $at;

        return true;
    }

    public function revoke(\DateTimeImmutable $at): void
    {
        if ($this->revokedAt !== null) {
            return;
        }

        $this->revokedAt = $at;
        $this->recordEvent(new ApiTokenRevoked((string) $this->id, (string) $this->agentId));
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }
}
