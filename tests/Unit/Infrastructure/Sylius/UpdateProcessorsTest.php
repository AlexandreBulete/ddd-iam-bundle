<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Unit\Infrastructure\Sylius;

use AlexandreBulete\DddIamBundle\Application\Command\ChangeRolePermissions\ChangeRolePermissionsCommand;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeUserRoles\ChangeUserRolesCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RelabelRole\RelabelRoleCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RenameUser\RenameUserCommand;
use AlexandreBulete\DddIamBundle\Application\Service\UserStatusChanger;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Password;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\RoleDefinitionResource;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\UserResource;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\UpdateRoleDefinitionProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\UpdateUserProcessor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Update;

/**
 * An edit form submits every field: only the ones the user changed may become
 * use cases — each is authorized and journaled on its own.
 */
final class UpdateProcessorsTest extends TestCase
{
    #[Test]
    public function an_untouched_user_form_dispatches_nothing(): void
    {
        $buses = new RecordingBuses(self::user());

        $this->updateUser($buses, UserResource::fromModel(self::user()));

        self::assertSame([], $buses->dispatched);
    }

    #[Test]
    public function changing_a_user_s_roles_dispatches_that_use_case_only(): void
    {
        $buses = new RecordingBuses(self::user());
        $form = UserResource::fromModel(self::user());
        $form->roles = ['admin', 'editor'];

        $this->updateUser($buses, $form);

        self::assertSame([ChangeUserRolesCommand::class], $buses->dispatched);
    }

    #[Test]
    public function renaming_a_user_dispatches_that_use_case_only(): void
    {
        $buses = new RecordingBuses(self::user());
        $form = UserResource::fromModel(self::user());
        $form->firstName = 'Ada';

        $this->updateUser($buses, $form);

        self::assertSame([RenameUserCommand::class], $buses->dispatched);
    }

    #[Test]
    public function an_untouched_role_form_dispatches_nothing(): void
    {
        $buses = new RecordingBuses(self::role());

        $this->updateRole($buses, RoleDefinitionResource::fromModel(self::role()));

        self::assertSame([], $buses->dispatched);
    }

    #[Test]
    public function each_changed_field_of_a_role_is_its_own_use_case(): void
    {
        $buses = new RecordingBuses(self::role());
        $form = RoleDefinitionResource::fromModel(self::role());
        $form->permissions = ['backoffice.access', 'iam.find_users'];

        $this->updateRole($buses, $form);
        self::assertSame([ChangeRolePermissionsCommand::class], $buses->dispatched);

        $buses = new RecordingBuses(self::role());
        $form = RoleDefinitionResource::fromModel(self::role());
        $form->label = 'Readers';

        $this->updateRole($buses, $form);
        self::assertSame([RelabelRoleCommand::class], $buses->dispatched);
    }

    private function updateUser(RecordingBuses $buses, UserResource $form): void
    {
        (new UpdateUserProcessor($buses, $buses, new UserStatusChanger($buses)))->process($form, new Update(), new Context());
    }

    private function updateRole(RecordingBuses $buses, RoleDefinitionResource $form): void
    {
        (new UpdateRoleDefinitionProcessor($buses, $buses))->process($form, new Update(), new Context());
    }

    private static function user(): User
    {
        return User::create(
            id: UserId::fromString('01J8ZQ7X3M5V9K2R4T6Y8B0C1D'),
            email: Email::fromString('ada@example.com'),
            password: Password::fromString('$2y$13$hashedhashedhashedhashedhashedhashedhashedhashedhashe'),
            roles: RoleSet::fromNames(['admin']),
            createdAt: new \DateTimeImmutable('2026-01-01 10:00'),
        );
    }

    private static function role(): RoleDefinition
    {
        return RoleDefinition::define(
            RoleDefinitionId::fromString('01J8ZQ7X3M5V9K2R4T6Y8B0C1E'),
            Role::fromName('reader'),
            'Reader',
            PermissionSet::of(['backoffice.access']),
            new \DateTimeImmutable('2026-01-01 10:00'),
        );
    }
}
