<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\DefineRole;

use AlexandreBulete\DddFoundation\Application\Activity\ActivityDescription;
use AlexandreBulete\DddFoundation\Application\Activity\JournaledInterface;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PermissionSet;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;

/**
 * @implements CommandInterface<RoleDefinition>
 */
final readonly class DefineRoleCommand implements CommandInterface, JournaledInterface
{
    public function __construct(
        public Role $role,
        public string $label,
        public PermissionSet $permissions,
    ) {}

    public function describeActivity(): ActivityDescription
    {
        return new ActivityDescription(
            subjectType: 'role',
            subjectId: $this->role->value(),
            summary: 'iam.activity.role_defined',
            summaryParams: ['role' => $this->label],
            details: ['permissions' => $this->permissions->toArray()],
        );
    }
}
