<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\RevokeApiToken;

use AlexandreBulete\DddFoundation\Application\Activity\ActivityDescription;
use AlexandreBulete\DddFoundation\Application\Activity\JournaledInterface;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\ApiTokenId;

/**
 * @implements CommandInterface<ApiToken>
 */
final readonly class RevokeApiTokenCommand implements CommandInterface, JournaledInterface
{
    public function __construct(
        public ApiTokenId $id,
    ) {}

    public function describeActivity(): ActivityDescription
    {
        return new ActivityDescription(
            subjectType: 'api_token',
            subjectId: (string) $this->id,
            summary: 'iam.activity.api_token_revoked',
        );
    }
}
