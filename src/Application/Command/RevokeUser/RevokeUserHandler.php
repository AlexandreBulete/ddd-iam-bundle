<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\RevokeUser;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddIamBundle\Domain\Exception\UserNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;

/**
 * Terminal deactivation. The row is kept so the audit trail keeps pointing at
 * a real user; GDPR erasure is a separate use case.
 */
#[AsCommandHandler]
final readonly class RevokeUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(RevokeUserCommand $command): User
    {
        $user = $this->userRepository->findById($command->id);

        if (null === $user) {
            throw new UserNotFoundException($command->id);
        }

        $user->revoke();

        $this->userRepository->save($user);

        return $user;
    }
}
