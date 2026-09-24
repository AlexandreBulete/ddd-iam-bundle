<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Service;

use AlexandreBulete\DddIamBundle\Domain\Exception\PasswordPolicyViolation;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainPassword;

/**
 * Domain port — enforces the deployment's password rules on a freshly
 * submitted plaintext password.
 *
 * The bundle ships a config-driven implementation
 * ({@see \AlexandreBulete\DddIamBundle\Infrastructure\Security\ConfigurablePasswordPolicy},
 * tuned through `iam.password_policy`). A project needing rules that config
 * cannot express — password history, HIBP lookup, per-tenant rules — aliases
 * its own implementation onto this interface and changes nothing else.
 */
interface PasswordPolicyInterface
{
    /**
     * @throws PasswordPolicyViolation if one or more rules fail
     */
    public function enforce(PlainPassword $password): void;
}
