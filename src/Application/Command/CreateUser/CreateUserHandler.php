<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\CreateUser;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\IdentityGeneratorInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PasswordHasherInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PasswordPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private PasswordPolicyInterface $passwordPolicy,
        private RoleCatalogInterface $roleCatalog,
        private IdentityGeneratorInterface $identities,
        private ClockInterface $clock,
    ) {}

    public function __invoke(CreateUserCommand $command): User
    {
        $this->passwordPolicy->enforce($command->password);

        $roles = $command->roles ?? $this->roleCatalog->defaults();
        $this->roleCatalog->assertKnown($roles);

        $user = User::create(
            id: $this->identities->nextUserId(),
            email: $command->email,
            password: $this->passwordHasher->hash($command->password),
            roles: $roles,
            createdAt: $this->clock->now(),
            firstName: $command->firstName,
            lastName: $command->lastName,
        );

        $this->userRepository->save($user);

        return $user;
    }
}
