<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenId;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * An agent, authenticated by one of its tokens, for the time of one request.
 *
 * No password: an agent never signs in with one (ADR 0011). The actor names
 * the token it came with, so that the journal can tell which one acted.
 */
final readonly class AgentSecurityUser implements UserInterface, ActorAwareInterface
{
    /** @var non-empty-string */
    private string $identifier;

    private string $name;

    public function __construct(
        Agent $agent,
        private ApiTokenId $tokenId,
    ) {
        $identifier = (string) $agent->id;
        if ($identifier === '') {
            throw new \LogicException('An agent always has an id.');
        }
        $this->identifier = $identifier;
        $this->name = $agent->name;
    }

    public function toActor(): Actor
    {
        return Actor::agent($this->identifier, $this->name, (string) $this->tokenId);
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * What Symfony needs to call someone authenticated. Authorization is by
     * permission (PermissionVoter), not by role.
     */
    public function getRoles(): array
    {
        return ['ROLE_AGENT'];
    }

    /**
     * Still part of UserInterface on Symfony 7; nothing to erase here.
     */
    public function eraseCredentials(): void
    {
    }
}
