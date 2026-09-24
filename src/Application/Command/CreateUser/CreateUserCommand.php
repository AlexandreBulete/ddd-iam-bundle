<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\CreateUser;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainPassword;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;

/**
 * @implements CommandInterface<User>
 */
final readonly class CreateUserCommand implements CommandInterface
{
    /**
     * @param RoleSet|null $roles null falls back to `iam.default_roles`, so a
     *                            caller that has no opinion on authorisation
     *                            does not have to invent one
     */
    public function __construct(
        public Email $email,
        public PlainPassword $password,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?RoleSet $roles = null,
    ) {}
}
