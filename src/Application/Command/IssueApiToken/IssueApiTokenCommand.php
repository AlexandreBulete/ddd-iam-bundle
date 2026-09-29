<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\IssueApiToken;

use AlexandreBulete\DddFoundation\Application\Activity\ActivityDescription;
use AlexandreBulete\DddFoundation\Application\Activity\JournaledInterface;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;

/**
 * The result carries the token in clear, once: it is shown to whoever issued
 * it and never again. The command itself holds no secret — it is journaled.
 *
 * @implements CommandInterface<IssuedApiToken>
 */
final readonly class IssueApiTokenCommand implements CommandInterface, JournaledInterface
{
    public function __construct(
        public AgentId $agentId,
        public string $label,
        public \DateTimeImmutable $expiresAt,
    ) {}

    public function describeActivity(): ActivityDescription
    {
        return new ActivityDescription(
            subjectType: 'agent',
            subjectId: (string) $this->agentId,
            summary: 'iam.activity.api_token_issued',
            summaryParams: ['token' => $this->label],
            details: ['label' => $this->label, 'expires_at' => $this->expiresAt->format(\DATE_ATOM)],
        );
    }
}
