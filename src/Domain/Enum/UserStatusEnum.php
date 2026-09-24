<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Domain\Enum;

use AlexandreBulete\DddFoundation\Domain\Trait\AsSelectableEnum;

/**
 * Lifecycle of an IAM user.
 *
 * Deliberately closed: unlike roles, which every project extends, these three
 * states exhaust what IAM needs to answer "may this account authenticate?".
 * A project-specific state (pending invitation, awaiting 2FA enrolment…)
 * belongs to a companion aggregate, not here.
 */
enum UserStatusEnum: string
{
    use AsSelectableEnum;

    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REVOKED = 'revoked';
}
