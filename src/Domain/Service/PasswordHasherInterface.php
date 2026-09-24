<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

use AlexandreBulete\DddIamBundle\Domain\ValueObject\Password;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainPassword;

/**
 * Domain port — turns a plaintext credential into the opaque hashed value
 * persisted on the User aggregate.
 *
 * The Domain stays agnostic of the algorithm; the adapter lives in
 * Infrastructure and delegates to Symfony's hasher pipeline.
 */
interface PasswordHasherInterface
{
    public function hash(PlainPassword $plain): Password;
}
