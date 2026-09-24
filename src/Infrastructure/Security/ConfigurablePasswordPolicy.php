<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Exception\PasswordPolicyViolation;
use AlexandreBulete\DddIamBundle\Domain\Service\PasswordPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainPassword;

/**
 * The default policy, driven by `iam.password_policy`.
 *
 * Every rule is collected before throwing, so a user fixing their password
 * learns everything that is wrong with it in one round trip instead of
 * discovering the requirements one rejection at a time.
 */
final readonly class ConfigurablePasswordPolicy implements PasswordPolicyInterface
{
    public function __construct(
        private int $minLength = 12,
        private bool $requireLetters = true,
        private bool $requireDigits = true,
        private bool $requireMixedCase = false,
        private bool $requireSpecialCharacter = false,
    ) {}

    public function enforce(PlainPassword $password): void
    {
        $value = $password->value();
        $violations = [];

        // Length is counted in characters, not bytes: an accented or non-Latin
        // password would otherwise be judged longer than the user sees it.
        if (mb_strlen($value) < $this->minLength) {
            $violations[] = sprintf('Password must be at least %d characters long.', $this->minLength);
        }

        if ($this->requireLetters && preg_match('/\p{L}/u', $value) !== 1) {
            $violations[] = 'Password must contain at least one letter.';
        }

        if ($this->requireDigits && preg_match('/\p{Nd}/u', $value) !== 1) {
            $violations[] = 'Password must contain at least one digit.';
        }

        if ($this->requireMixedCase
            && (preg_match('/\p{Lu}/u', $value) !== 1 || preg_match('/\p{Ll}/u', $value) !== 1)
        ) {
            $violations[] = 'Password must contain both uppercase and lowercase letters.';
        }

        if ($this->requireSpecialCharacter && preg_match('/[^\p{L}\p{Nd}]/u', $value) !== 1) {
            $violations[] = 'Password must contain at least one special character.';
        }

        if ($violations !== []) {
            throw new PasswordPolicyViolation($violations);
        }
    }
}
