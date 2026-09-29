<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Unit\Domain;

use AlexandreBulete\DddIamBundle\Domain\Event\RoleDefined;
use AlexandreBulete\DddIamBundle\Domain\Event\RolePermissionsChanged;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoleDefinitionTest extends TestCase
{
    #[Test]
    public function a_role_grants_exactly_its_permissions(): void
    {
        $role = self::reader();

        self::assertTrue($role->grants('client.find_clients'));
        self::assertFalse($role->grants('client.delete_client'));
        self::assertInstanceOf(RoleDefined::class, $role->releaseEvents()[0]);
    }

    #[Test]
    public function super_admin_grants_everything_even_what_does_not_exist_yet(): void
    {
        $superAdmin = RoleDefinition::superAdmin(RoleDefinitionId::generate(), 'Super admin', new \DateTimeImmutable());

        self::assertTrue($superAdmin->grants('anything.tomorrow'));
        self::assertEquals(
            PermissionSet::of(['a.b', 'c.d']),
            $superAdmin->grantedAmong(PermissionSet::of(['a.b', 'c.d'])),
        );
    }

    #[Test]
    public function super_admin_cannot_be_restricted(): void
    {
        $this->expectException(\DomainException::class);

        RoleDefinition::superAdmin(RoleDefinitionId::generate(), 'Super admin', new \DateTimeImmutable())
            ->changePermissions(PermissionSet::of(['a.b']), new \DateTimeImmutable());
    }

    #[Test]
    public function super_admin_cannot_be_removed(): void
    {
        $this->expectException(\DomainException::class);

        RoleDefinition::superAdmin(RoleDefinitionId::generate(), 'Super admin', new \DateTimeImmutable())->remove();
    }

    #[Test]
    public function nobody_defines_a_second_super_admin(): void
    {
        $this->expectException(\DomainException::class);

        RoleDefinition::define(RoleDefinitionId::generate(), Role::fromName('super_admin'), 'Fake', PermissionSet::none(), new \DateTimeImmutable());
    }

    #[Test]
    public function changing_permissions_records_before_and_after(): void
    {
        $role = self::reader();
        $role->releaseEvents();

        $role->changePermissions(PermissionSet::of(['client.find_clients', 'client.show_client']), new \DateTimeImmutable('2026-10-01'));

        $event = $role->releaseEvents()[0];
        self::assertInstanceOf(RolePermissionsChanged::class, $event);
        self::assertSame(['client.find_clients'], $event->previousPermissions);
        self::assertSame(['client.find_clients', 'client.show_client'], $event->permissions);
    }

    #[Test]
    public function a_permission_set_is_ordered_and_without_duplicates(): void
    {
        self::assertTrue(PermissionSet::of(['b.x', 'a.y', 'b.x'])->equals(PermissionSet::of(['a.y', 'b.x'])));

        $this->expectException(\InvalidArgumentException::class);
        PermissionSet::of(['Not A Permission']);
    }

    private static function reader(): RoleDefinition
    {
        return RoleDefinition::define(
            RoleDefinitionId::generate(),
            Role::fromName('reader'),
            'Lecteur',
            PermissionSet::of(['client.find_clients']),
            new \DateTimeImmutable('2026-10-01'),
        );
    }
}
