<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\CreateUser\CreateUserCommand;
use AlexandreBulete\DddIamBundle\Application\Service\UserStatusChanger;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainPassword;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\UserResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Webmozart\Assert\Assert;

final readonly class CreateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private UserStatusChanger $statusChanger,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): UserResource
    {
        Assert::isInstanceOf($data, UserResource::class);
        Assert::notNull($data->email);
        Assert::notNull($data->password);

        /** @var User $user */
        $user = $this->commandBus->dispatch(new CreateUserCommand(
            email: new Email($data->email),
            password: new PlainPassword($data->password),
            firstName: $data->firstName,
            lastName: $data->lastName,
            // No roles picked in the form → the command falls back to
            // `iam.default_roles` rather than creating a user who can do nothing.
            roles: $data->roles === [] ? null : RoleSet::fromNames($data->roles),
        ));

        // A user is always born active; setting another status is a second,
        // explicit transition rather than a constructor argument.
        if ($data->status !== null) {
            $user = $this->statusChanger->changeTo($user, $data->status);
        }

        return UserResource::fromModel($user);
    }
}
