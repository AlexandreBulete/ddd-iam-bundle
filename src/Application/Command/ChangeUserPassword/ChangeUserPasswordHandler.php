<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ChangeUserPassword;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddIamBundle\Domain\Exception\UserNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PasswordHasherInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PasswordPolicyInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class ChangeUserPasswordHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private PasswordPolicyInterface $passwordPolicy,
        private ClockInterface $clock,
    ) {}

    public function __invoke(ChangeUserPasswordCommand $command): User
    {
        $user = $this->userRepository->findById($command->id);

        if (null === $user) {
            throw new UserNotFoundException($command->id);
        }

        $this->passwordPolicy->enforce($command->password);

        $user->changePassword($this->passwordHasher->hash($command->password), $this->clock->now());

        $this->userRepository->save($user);

        return $user;
    }
}
