<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Application\Command\IssueApiToken;

use AlexandreBulete\DddIamBundle\Domain\Model\ApiToken;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainApiToken;

/**
 * A token just issued, with the only copy of its clear form.
 */
final readonly class IssuedApiToken
{
    public function __construct(
        public ApiToken $token,
        public PlainApiToken $plain,
    ) {}
}
