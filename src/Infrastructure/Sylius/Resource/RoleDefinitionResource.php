<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource;

use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\RoleDefinitionGrid;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\CreateRoleDefinitionProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\DeleteRoleDefinitionProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\UpdateRoleDefinitionProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\RoleDefinitionItemProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Type\RoleDefinitionType;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Metadata\Create;
use Sylius\Resource\Metadata\Delete;
use Sylius\Resource\Metadata\Index;
use Sylius\Resource\Metadata\Update;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Back-office DTO for a role definition (ADR 0008) — same pattern as
 * UserResource: flat fields in, commands out through the processors.
 */
#[AsResource(
    alias: 'iam.role',
    section: 'admin',
    formType: RoleDefinitionType::class,
    templatesDir: '@SyliusAdminUi/crud',
    routePrefix: '/admin',
    driver: false,
    operations: [
        new Create(processor: CreateRoleDefinitionProcessor::class),
        new Update(provider: RoleDefinitionItemProvider::class, processor: UpdateRoleDefinitionProcessor::class),
        new Delete(provider: RoleDefinitionItemProvider::class, processor: DeleteRoleDefinitionProcessor::class),
        new Index(grid: RoleDefinitionGrid::class),
    ],
)]
final class RoleDefinitionResource implements ResourceInterface
{
    /**
     * @param list<string> $permissions
     */
    public function __construct(
        public ?AbstractUid $id = null,
        // The technical name users carry (`chef_de_projet` → ROLE_CHEF_DE_PROJET).
        // Set on creation only: renaming would silently strip it from users.
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Regex('/^[a-z][a-z0-9_]*$/', message: 'iam.role.name_format')]
        public ?string $name = null,
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public ?string $label = null,
        public array $permissions = [],
        public bool $system = false,
        public ?string $permissionsSummary = null,
    ) {}

    public function getId(): ?AbstractUid
    {
        return $this->id;
    }

    public static function fromModel(RoleDefinition $definition): self
    {
        return new self(
            id: $definition->id->value(),
            name: $definition->role->name(),
            label: $definition->label,
            permissions: $definition->permissions->toArray(),
            system: $definition->system,
            permissionsSummary: $definition->system ? '∞' : (string) count($definition->permissions),
        );
    }
}
