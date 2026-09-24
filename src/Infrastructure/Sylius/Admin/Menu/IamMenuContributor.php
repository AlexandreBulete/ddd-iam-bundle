<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu;

use AlexandreBulete\DddSyliusBundle\Admin\Menu\MenuContributorInterface;
use Knp\Menu\ItemInterface;

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
    protected function iamRoot(ItemInterface $menu): ItemInterface
    {
        return $menu->getChild('iam') ?? $menu
            ->addChild('iam')
            ->setLabel('iam.menu.root')
            ->setLabelAttribute('icon', 'tabler:shield-lock');
    }
}
