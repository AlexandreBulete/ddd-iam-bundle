<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ChangeUserRoles;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddIamBundle\Domain\Exception\UserNotFoundException;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\GrantPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use Psr\Clock\ClockInterface;

#[AsCommandHandler]
final readonly class ChangeUserRolesHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleCatalogInterface $roleCatalog,
        private ClockInterface $clock,
        private GrantPolicyInterface $grantPolicy,
    ) {}

    public function __invoke(ChangeUserRolesCommand $command): User
    {
        $user = $this->userRepository->findById($command->id);

        if (null === $user) {
            throw new UserNotFoundException($command->id);
        }

        // Checked here rather than in the aggregate: which roles exist is
        // data (the role definitions), not an invariant of the user.
        $this->roleCatalog->assertKnown($command->roles);

        // Taking a role away changes access as much as granting one: whoever
        // acts must hold both the new and the previous roles' permissions.
        $this->grantPolicy->assertMayAssign($command->roles->union($user->roles));

        $user->changeRoles($command->roles, $this->clock->now());

        $this->userRepository->save($user);

        return $user;
    }
}
