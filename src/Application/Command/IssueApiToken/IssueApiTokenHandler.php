<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\IssueApiToken;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Domain\Exception\EntityNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\ApiTokenRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\IdentityGeneratorInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\SecretGeneratorInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainApiToken;
use Psr\Clock\ClockInterface;

/**
 * `$tokenPrefix` is `iam.api_tokens.prefix`: what makes this application's
 * tokens recognisable to a secret scanner.
 */
#[AsCommandHandler]
final readonly class IssueApiTokenHandler
{
    public function __construct(
        private AgentRepositoryInterface $agents,
        private ApiTokenRepositoryInterface $tokens,
        private IdentityGeneratorInterface $identities,
        private SecretGeneratorInterface $secrets,
        private ClockInterface $clock,
        private string $tokenPrefix,
    ) {}

    public function __invoke(IssueApiTokenCommand $command): IssuedApiToken
    {
        $agent = $this->agents->findById($command->agentId)
            ?? throw new EntityNotFoundException(Agent::class, $command->agentId);
        if (!$agent->isActive()) {
            throw new \DomainException('Only an active agent receives tokens.');
        }

        $plain = PlainApiToken::compose($this->tokenPrefix, $this->identities->nextApiTokenId(), $this->secrets->secret());
        $token = ApiToken::issue($plain->id, $agent->id, $command->label, $plain->digest(), $this->clock->now(), $command->expiresAt);
        $this->tokens->save($token);

        return new IssuedApiToken($token, $plain);
    }
}
