<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Service;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\ReactivateUser\ReactivateUserCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RevokeUser\RevokeUserCommand;
use AlexandreBulete\DddIamBundle\Application\Command\SuspendUser\SuspendUserCommand;
use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Model\User;

/**
 * Translates "set this user's status to X" — the shape a back-office form
 * speaks — into the named use case that expresses it.
 *
 * The admin UI edits a status field; the Domain exposes intentions
 * (suspend/reactivate/revoke). Without this adapter every caller would
 * re-implement the same match, and the CRUD vocabulary would leak into the
 * Application layer.
 */
final readonly class UserStatusChanger
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function changeTo(User $user, UserStatusEnum $target): User
    {
        if ($user->status->toEnum() === $target) {
            return $user;
        }

        $command = match ($target) {
            UserStatusEnum::ACTIVE => new ReactivateUserCommand($user->id),
            UserStatusEnum::SUSPENDED => new SuspendUserCommand($user->id),
            UserStatusEnum::REVOKED => new RevokeUserCommand($user->id),
        };

        /** @var User $updated */
        $updated = $this->commandBus->dispatch($command);

        return $updated;
    }
}
