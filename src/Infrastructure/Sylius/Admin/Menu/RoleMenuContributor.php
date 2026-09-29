<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.menu_contributor', ['priority' => 15])]
final readonly class RoleMenuContributor extends IamMenuContributor
{
    public function contribute(ItemInterface $menu): void
    {
        if (!$this->allowed('iam.find_role_definitions')) {
            return;
        }

        $this->iamRoot($menu)
            ->addChild('iam_roles', ['route' => 'iam_admin_role_index'])
            ->setLabel('iam.role.index');
    }
}
