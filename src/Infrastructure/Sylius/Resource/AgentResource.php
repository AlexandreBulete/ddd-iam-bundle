<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource;

use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\AgentGrid;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\CreateAgentProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\DeleteAgentProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\UpdateAgentProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\AgentItemProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Type\AgentType;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Metadata\Create;
use Sylius\Resource\Metadata\Delete;
use Sylius\Resource\Metadata\Index;
use Sylius\Resource\Metadata\Update;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Back-office DTO for an agent account (ADR 0011) — same pattern as
 * UserResource. Delete revokes: the journal keeps pointing at a real account.
 */
#[AsResource(
    alias: 'iam.agent',
    section: 'admin',
    formType: AgentType::class,
    templatesDir: '@SyliusAdminUi/crud',
    routePrefix: '/admin',
    driver: false,
    operations: [
        new Create(processor: CreateAgentProcessor::class),
        new Update(provider: AgentItemProvider::class, processor: UpdateAgentProcessor::class),
        new Delete(provider: AgentItemProvider::class, processor: DeleteAgentProcessor::class),
        new Index(grid: AgentGrid::class),
    ],
)]
final class AgentResource implements ResourceInterface
{
    /**
     * @param list<string> $roles role names (`chef_de_projet`)
     */
    public function __construct(
        public ?AbstractUid $id = null,
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public ?string $name = null,
        #[Assert\Length(max: 500)]
        public ?string $description = null,
        public array $roles = [],
        public ?string $rolesLabel = null,
        public ?UserStatusEnum $status = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {}

    public function getId(): ?AbstractUid
    {
        return $this->id;
    }

    public static function fromModel(Agent $agent): self
    {
        return new self(
            id: $agent->id->value(),
            name: $agent->name,
            description: $agent->description,
            roles: $agent->roles->toNames(),
            rolesLabel: implode(', ', $agent->roles->toNames()),
            status: $agent->status->toEnum(),
            createdAt: $agent->createdAt,
        );
    }
}
