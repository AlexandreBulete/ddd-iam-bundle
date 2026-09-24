<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ChangeUserRoles;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddIamBundle\Domain\Exception\UserNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;

#[AsCommandHandler]
final readonly class ChangeUserRolesHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleCatalogInterface $roleCatalog,
    ) {}

    public function __invoke(ChangeUserRolesCommand $command): User
    {
        $user = $this->userRepository->findById($command->id);

        if (null === $user) {
            throw new UserNotFoundException($command->id);
        }

        // Checked here rather than in the aggregate: the catalogue is
        // deployment configuration, and a Domain invariant must not depend on
        // what a YAML file happens to declare today.
        $this->roleCatalog->assertKnown($command->roles);

        $user->changeRoles($command->roles);

        $this->userRepository->save($user);

        return $user;
    }
}
