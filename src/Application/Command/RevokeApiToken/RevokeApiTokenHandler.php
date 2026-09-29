<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\RevokeApiToken;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Domain\Exception\EntityNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Domain\Repository\ApiTokenRepositoryInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class RevokeApiTokenHandler
{
    public function __construct(
        private ApiTokenRepositoryInterface $tokens,
        private ClockInterface $clock,
    ) {}

    public function __invoke(RevokeApiTokenCommand $command): ApiToken
    {
        $token = $this->tokens->findById($command->id)
            ?? throw new EntityNotFoundException(ApiToken::class, $command->id);

        $token->revoke($this->clock->now());
        $this->tokens->save($token);

        return $token;
    }
}
