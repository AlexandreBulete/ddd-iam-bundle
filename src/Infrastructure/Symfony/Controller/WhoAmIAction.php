<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Controller;

use AlexandreBulete\DddIamBundle\Infrastructure\Security\IamPermissionChecker;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorAwareInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * "Who am I, and what may I do?" — what an agent's CI calls to check its
 * token before relying on it (ADR 0011). Answers for any authenticated
 * account; the firewall in front decides who gets this far.
 */
final class WhoAmIAction
{
    public function __construct(
        private readonly Security $security,
        private readonly PermissionRegistry $registry,
        private readonly IamPermissionChecker $checker,
    ) {}

    #[Route(path: '/api/iam/me', name: 'iam_api_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof ActorAwareInterface) {
            return new JsonResponse(['error' => 'Not authenticated.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $actor = $user->toActor();

        return new JsonResponse([
            'kind' => $actor->kind->value,
            'id' => $actor->id,
            'name' => $actor->label,
            'token' => $actor->credential,
            'permissions' => array_values(array_filter(
                $this->registry->all(),
                fn (string $permission): bool => $this->checker->isGranted($actor, $permission),
            )),
        ]);
    }
}
