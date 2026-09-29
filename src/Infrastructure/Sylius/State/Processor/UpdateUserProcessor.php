<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeUserEmail\ChangeUserEmailCommand;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeUserPassword\ChangeUserPasswordCommand;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeUserRoles\ChangeUserRolesCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RenameUser\RenameUserCommand;
use AlexandreBulete\DddIamBundle\Application\Query\FindUser\FindUserQuery;
use AlexandreBulete\DddIamBundle\Application\Service\UserStatusChanger;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainPassword;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\UserResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Symfony\Component\Uid\Ulid;
use Webmozart\Assert\Assert;

/**
 * Maps one "edit user" form submission onto the named use cases it implies.
 *
 * A form submits every field; only what the user changed becomes a command.
 * Each use case is authorized and journaled on its own: re-sending an
 * unchanged name would require `iam.rename_user` from someone who only
 * changed roles, and journal a rename that never happened. The comparisons
 * use the value objects' equality, so no rule of the aggregate is duplicated.
 */
final readonly class UpdateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private UserStatusChanger $statusChanger,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): mixed
    {
        Assert::isInstanceOf($data, UserResource::class);
        Assert::isInstanceOf($data->id, Ulid::class);

        $userId = UserId::fromUlid($data->id);

        /** @var User $user */
        $user = $this->queryBus->ask(new FindUserQuery($userId));

        if ($data->firstName !== $user->firstName || $data->lastName !== $user->lastName) {
            /** @var User $user */
            $user = $this->commandBus->dispatch(new RenameUserCommand(
                id: $userId,
                firstName: $data->firstName,
                lastName: $data->lastName,
            ));
        }

        if ($data->email !== null && !$user->email->equals(new Email($data->email))) {
            /** @var User $user */
            $user = $this->commandBus->dispatch(new ChangeUserEmailCommand(
                id: $userId,
                email: new Email($data->email),
            ));
        }

        // Blank means "leave it alone". The form is pre-filled with the hash,
        // so a submitted value equal to the hash is also a no-change: only a
        // genuinely new plaintext gets through.
        if ($data->password !== null && $data->password !== '' && $data->password !== $user->password->value()) {
            /** @var User $user */
            $user = $this->commandBus->dispatch(new ChangeUserPasswordCommand(
                id: $userId,
                password: new PlainPassword($data->password),
            ));
        }

        $roles = RoleSet::fromNames($data->roles);
        if (!$roles->equals($user->roles)) {
            /** @var User $user */
            $user = $this->commandBus->dispatch(new ChangeUserRolesCommand(
                id: $userId,
                roles: $roles,
            ));
        }

        if ($data->status !== null) {
            $user = $this->statusChanger->changeTo($user, $data->status);
        }

        return UserResource::fromModel($user);
    }
}
