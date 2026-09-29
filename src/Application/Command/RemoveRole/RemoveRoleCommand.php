<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\RemoveRole;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;

/**
 * @implements CommandInterface<void>
 */
final readonly class RemoveRoleCommand implements CommandInterface
{
    public function __construct(
        public RoleDefinitionId $id,
    ) {}
}
