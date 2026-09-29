<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * The tag name is imposed by DddSyliusBundle's MenuBuilder, which iterates
 * `app.menu_contributor` — that is the contract between the two bundles.
 */
#[AutoconfigureTag('app.menu_contributor', ['priority' => 10])]
final readonly class UserMenuContributor extends IamMenuContributor
{
    public function contribute(ItemInterface $menu): void
    {
        if (!$this->allowed('iam.find_users')) {
            return;
        }

        $this->iamRoot($menu)
            ->addChild('iam_users', ['route' => 'iam_admin_user_index'])
            ->setLabel('iam.user.index');
    }
}
