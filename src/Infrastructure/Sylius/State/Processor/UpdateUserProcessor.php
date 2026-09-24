<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeUserEmail\ChangeUserEmailCommand;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeUserPassword\ChangeUserPasswordCommand;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeUserRoles\ChangeUserRolesCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RenameUser\RenameUserCommand;
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
 * Every command is dispatched unconditionally where it is safe to do so: the
 * aggregate is idempotent, so re-sending an unchanged name records nothing.
 * That keeps this class free of change detection, which would otherwise
 * duplicate — and eventually contradict — the rules inside the aggregate.
 */
final readonly class UpdateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private UserStatusChanger $statusChanger,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): mixed
    {
        Assert::isInstanceOf($data, UserResource::class);
        Assert::isInstanceOf($data->id, Ulid::class);

        $userId = UserId::fromUlid($data->id);

        /** @var User $user */
        $user = $this->commandBus->dispatch(new RenameUserCommand(
            id: $userId,
            firstName: $data->firstName,
            lastName: $data->lastName,
        ));

        if ($data->email !== null) {
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

        /** @var User $user */
        $user = $this->commandBus->dispatch(new ChangeUserRolesCommand(
            id: $userId,
            roles: RoleSet::fromNames($data->roles),
        ));

        if ($data->status !== null) {
            $user = $this->statusChanger->changeTo($user, $data->status);
        }

        return UserResource::fromModel($user);
    }
}
