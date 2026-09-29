<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu;

use AlexandreBulete\DddSyliusBundle\Admin\Menu\MenuContributorInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Shared root for the bundle's menu entries.
 *
 * `iamRoot()` is deliberately stateless — it returns the node instead of
 * caching it on the instance. Menu contributors are shared services, and a
 * cached node would survive across `createMenu()` calls, attaching the second
 * request's children to the first request's (already rendered) menu.
 */
abstract readonly class IamMenuContributor implements MenuContributorInterface
{
    public function __construct(
        private AuthorizationCheckerInterface $authorization,
    ) {}

    /**
     * A menu entry shows only what its screen will let you see: the same
     * permission the screen's query requires (ADR 0008).
     */
    protected function allowed(string $permission): bool
    {
        return $this->authorization->isGranted($permission);
    }

    protected function iamRoot(ItemInterface $menu): ItemInterface
    {
        return $menu->getChild('iam') ?? $menu
            ->addChild('iam')
            ->setLabel('iam.menu.root')
            ->setLabelAttribute('icon', 'tabler:shield-lock');
    }
}
