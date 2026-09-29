<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource;

use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\ApiTokenGrid;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\IssueApiTokenProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\RevokeApiTokenProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\ApiTokenItemProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Type\ApiTokenType;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Metadata\Create;
use Sylius\Resource\Metadata\Delete;
use Sylius\Resource\Metadata\Index;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Back-office DTO for an API token (ADR 0011). Issued, listed, revoked —
 * never edited: a token that should change is replaced.
 */
#[AsResource(
    alias: 'iam.api_token',
    section: 'admin',
    formType: ApiTokenType::class,
    templatesDir: '@SyliusAdminUi/crud',
    routePrefix: '/admin',
    driver: false,
    operations: [
        new Create(processor: IssueApiTokenProcessor::class),
        new Delete(provider: ApiTokenItemProvider::class, processor: RevokeApiTokenProcessor::class),
        new Index(grid: ApiTokenGrid::class),
    ],
)]
final class ApiTokenResource implements ResourceInterface
{
    /** A token about to expire is flagged this long before. */
    private const EXPIRY_WARNING = 'P14D';

    public function __construct(
        public ?AbstractUid $id = null,
        #[Assert\NotBlank]
        public ?string $agentId = null,
        public ?string $agentName = null,
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public ?string $label = null,
        #[Assert\Choice(choices: [30, 90, 180, 365])]
        public int $lifetimeDays = 90,
        public ?\DateTimeImmutable $issuedAt = null,
        public ?\DateTimeImmutable $expiresAt = null,
        public ?\DateTimeImmutable $lastUsedAt = null,
        public ?string $state = null,
    ) {}

    public function getId(): ?AbstractUid
    {
        return $this->id;
    }

    public static function fromModel(ApiToken $token, string $agentName, \DateTimeImmutable $now): self
    {
        return new self(
            id: $token->id->value(),
            agentId: (string) $token->agentId,
            agentName: $agentName,
            label: $token->label,
            issuedAt: $token->issuedAt,
            expiresAt: $token->expiresAt,
            lastUsedAt: $token->lastUsedAt,
            state: match (true) {
                $token->isRevoked() => 'revoked',
                !$token->isUsableAt($now) => 'expired',
                !$token->isUsableAt($now->add(new \DateInterval(self::EXPIRY_WARNING))) => 'expiring',
                default => 'active',
            },
        );
    }
}
