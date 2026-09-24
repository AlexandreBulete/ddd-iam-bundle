<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Controller;

use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Feeds a select2/autocomplete widget that needs to pick a user by email.
 *
 * Behind the admin firewall (the route sits under the admin prefix), so it is
 * not an open user-enumeration endpoint. The two-character minimum keeps an
 * empty keystroke from returning the whole directory.
 */
final class AutocompleteUserByEmailAction
{
    private const MIN_QUERY_LENGTH = 2;
    private const MAX_RESULTS = 50;

    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    #[Route(
        path: '/admin/iam/users/autocomplete',
        name: 'iam_admin_user_autocomplete',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $search = (string) $request->query->get('q', '');
        $limit = min($request->query->getInt('limit', 20), self::MAX_RESULTS);

        if (mb_strlen(trim($search)) < self::MIN_QUERY_LENGTH) {
            return new JsonResponse(['results' => []]);
        }

        $results = array_map(
            static fn ($user): array => [
                'id' => (string) $user->id,
                'text' => $user->email->value(),
            ],
            $this->users->findByEmailLike($search, $limit),
        );

        return new JsonResponse(['results' => $results]);
    }
}
