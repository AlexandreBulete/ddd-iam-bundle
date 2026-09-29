<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\ApiTokenRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainApiToken;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Authenticates an agent from `Authorization: Bearer <token>` (ADR 0011), for
 * Symfony's `access_token` authenticator:
 *
 *     firewalls:
 *         api:
 *             pattern: ^/api
 *             stateless: true
 *             access_token:
 *                 token_handler: AlexandreBulete\DddIamBundle\Infrastructure\Security\ApiTokenHandler
 *
 * Every refusal looks the same — malformed, unknown, wrong secret, expired,
 * revoked, agent not active: whoever probes learns nothing.
 *
 * Authentication is not a use case: nobody acts yet, so this reads the
 * repositories directly, and "last used" is bookkeeping, not an action.
 */
final readonly class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private ApiTokenRepositoryInterface $tokens,
        private AgentRepositoryInterface $agents,
        private ClockInterface $clock,
        private string $tokenPrefix,
    ) {}

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $plain = PlainApiToken::parse($this->tokenPrefix, $accessToken) ?? throw self::refused();
        $token = $this->tokens->findById($plain->id);
        $now = $this->clock->now();
        if ($token === null || !$token->authenticates($plain->secret, $now)) {
            throw self::refused();
        }

        $agent = $this->agents->findById($token->agentId);
        if ($agent === null || !$agent->isActive()) {
            throw self::refused();
        }

        if ($token->markUsed($now)) {
            $this->tokens->save($token);
        }

        $user = new AgentSecurityUser($agent, $token->id);

        return new UserBadge($user->getUserIdentifier(), static fn (): AgentSecurityUser => $user);
    }

    private static function refused(): BadCredentialsException
    {
        return new BadCredentialsException('Invalid API token.');
    }
}
