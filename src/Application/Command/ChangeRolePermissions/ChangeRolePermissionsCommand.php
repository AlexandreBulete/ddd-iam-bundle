<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\ChangeRolePermissions;

use AlexandreBulete\DddFoundation\Application\Activity\ActivityDescription;
use AlexandreBulete\DddFoundation\Application\Activity\JournaledInterface;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;

/**
 * @implements CommandInterface<RoleDefinition>
 */
final readonly class ChangeRolePermissionsCommand implements CommandInterface, JournaledInterface
{
    public function __construct(
        public RoleDefinitionId $id,
        public PermissionSet $permissions,
    ) {}

    public function describeActivity(): ActivityDescription
    {
        return new ActivityDescription(
            subjectType: 'role',
            subjectId: (string) $this->id,
            summary: 'iam.activity.role_permissions_changed',
            details: ['permissions' => $this->permissions->toArray()],
        );
    }
}
