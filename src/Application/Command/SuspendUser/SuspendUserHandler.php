<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\SuspendUser;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddIamBundle\Domain\Exception\UserNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use Psr\Clock\ClockInterface;

/**
 * Temporarily blocks authentication. Reversible — see ReactivateUserCommand.
 */
#[AsCommandHandler]
final readonly class SuspendUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ClockInterface $clock,
    ) {}

    public function __invoke(SuspendUserCommand $command): User
    {
        $user = $this->userRepository->findById($command->id);

        if (null === $user) {
            throw new UserNotFoundException($command->id);
        }

        $user->suspend($this->clock->now());

        $this->userRepository->save($user);

        return $user;
    }
}
