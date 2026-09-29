<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\RelabelRole;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;

/**
 * @implements CommandInterface<RoleDefinition>
 */
final readonly class RelabelRoleCommand implements CommandInterface
{
    public function __construct(
        public RoleDefinitionId $id,
        public string $label,
    ) {}
}
