<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Exception;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\UserId;

final class UserNotFoundException extends \RuntimeException
{
    public function __construct(UserId $id, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Cannot find user with id %s', (string) $id), $code, $previous);
    }
}
