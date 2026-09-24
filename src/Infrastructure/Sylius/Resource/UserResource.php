<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource;

use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\UserGrid;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\CreateUserProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\DeleteUserProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\UpdateUserProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\UserBulkItemsProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\UserItemProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Type\UserType;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Metadata\BulkDelete;
use Sylius\Resource\Metadata\Create;
use Sylius\Resource\Metadata\Delete;
use Sylius\Resource\Metadata\Index;
use Sylius\Resource\Metadata\Update;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Read/write DTO for the back office — deliberately NOT the aggregate.
 *
 * The CRUD screens speak in flat, nullable, string-ish fields; the aggregate
 * speaks in value objects and intentions. Binding a form straight onto the
 * aggregate would force public setters on it and dissolve every invariant it
 * protects. The processors below translate one into the other.
 *
 * `driver: false` — Sylius must not try to hydrate this from Doctrine: the
 * providers go through the query bus instead.
 *
 * `alias: 'iam.user'` fixes the generated route names (`iam_admin_user_*`)
 * instead of letting them be derived from the namespace, which would change
 * under anyone who moved the class.
 */
#[AsResource(
    alias: 'iam.user',
    section: 'admin',
    formType: UserType::class,
    templatesDir: '@SyliusAdminUi/crud',
    routePrefix: '/admin',
    driver: false,
    operations: [
        new Create(processor: CreateUserProcessor::class),
        new Update(provider: UserItemProvider::class, processor: UpdateUserProcessor::class),
        new Delete(provider: UserItemProvider::class, processor: DeleteUserProcessor::class),
        new BulkDelete(provider: UserBulkItemsProvider::class, processor: DeleteUserProcessor::class),
        new Index(grid: UserGrid::class),
    ],
)]
final class UserResource implements ResourceInterface
{
    /**
     * @param list<string> $roles role configuration names (`admin`), not `ROLE_ADMIN`
     */
    public function __construct(
        public ?AbstractUid $id = null,

        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Email]
        public ?string $email = null,

        // Required on create only: left blank on update it means "keep the
        // current password", which is what an admin expects from an edit form.
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Length(min: 8, max: 255)]
        public ?string $password = null,

        #[Assert\Length(max: 100)]
        public ?string $firstName = null,

        #[Assert\Length(max: 100)]
        public ?string $lastName = null,

        public array $roles = [],

        // Precomputed for the grid: a list cannot be rendered by a StringField,
        // and adding a Twig template for a comma-separated join is not worth it.
        public ?string $rolesLabel = null,

        public ?UserStatusEnum $status = null,

        public ?\DateTimeImmutable $createdAt = null,
    ) {}

    public function getId(): ?AbstractUid
    {
        return $this->id;
    }

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id->value(),
            email: $user->email->value(),
            // The hash, never the plaintext — the form field is write-only and
            // its value is ignored unless the admin types something new.
            password: $user->password->value(),
            firstName: $user->firstName,
            lastName: $user->lastName,
            roles: $user->roles->toNames(),
            rolesLabel: implode(', ', $user->roles->toNames()),
            status: $user->status->toEnum(),
            createdAt: $user->createdAt,
        );
    }
}
