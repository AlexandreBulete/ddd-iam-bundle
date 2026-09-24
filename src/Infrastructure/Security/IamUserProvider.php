<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Security;

use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddSymfonyBundle\Security\AbstractDomainUserProvider;
use AlexandreBulete\DddSymfonyBundle\Security\SecurityUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Bridges the IAM aggregate to Symfony Security.
 *
 * Roles come from the aggregate, so `iam.roles` and the back-office role
 * editor are the single source of truth for authorisation — nothing is
 * hardcoded here.
 *
 * STATUS ENFORCEMENT happens at load time rather than through a
 * {@see \Symfony\Component\Security\Core\User\UserCheckerInterface}. The
 * trade-off is deliberate:
 *
 *   - a UserChecker is the idiomatic place, and `checkPostAuth()` would avoid
 *     disclosing an account's status to someone who does not know its
 *     password;
 *   - but it has to be wired per firewall (`user_checker:`), and a firewall
 *     that forgets it authenticates suspended and revoked accounts silently.
 *
 * For a bundle, a check that cannot be forgotten beats a check that leaks
 * slightly less. The disclosure is bounded — it requires knowing a valid
 * address, and back-office addresses are not secrets. Move to a UserChecker
 * the day post-authentication checks appear (password expiry, MFA enrolment),
 * since those genuinely need the credential verified first.
 *
 * @extends AbstractDomainUserProvider<User>
 */
final class IamUserProvider extends AbstractDomainUserProvider
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    protected function loadDomainUser(string $identifier): ?User
    {
        return $this->userRepository->findOneByEmail($identifier);
    }

    protected function toSecurityUser(object $domainUser): SecurityUser
    {
        if (!$domainUser->status->canLogin()) {
            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans(match ($domainUser->status->toEnum()) {
                    UserStatusEnum::SUSPENDED => 'iam.security.account_suspended',
                    UserStatusEnum::REVOKED => 'iam.security.account_revoked',
                    default => 'iam.security.account_inactive',
                }),
            );
        }

        return new SecurityUser(
            userIdentifier: $domainUser->email->value(),
            hashedPassword: $domainUser->password->value(),
            roles: $domainUser->roles->toStrings(),
        );
    }
}
