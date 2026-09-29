<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorAwareInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Permissions in Symfony Security: `is_granted('iam.find_users')` in a
 * template, `roles: backoffice.access` in access_control — same answer as the
 * bus middleware, from the same source.
 *
 * @extends Voter<string, mixed>
 */
final class PermissionVoter extends Voter
{
    public function __construct(
        private readonly PermissionRegistry $registry,
        private readonly IamPermissionChecker $checker,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->registry->has($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        return $user instanceof ActorAwareInterface && $this->checker->isGranted($user->toActor(), $attribute);
    }
}
