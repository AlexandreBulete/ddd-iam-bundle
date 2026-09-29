<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.menu_contributor', ['priority' => 13])]
final readonly class ApiTokenMenuContributor extends IamMenuContributor
{
    public function contribute(ItemInterface $menu): void
    {
        if (!$this->allowed('iam.find_api_tokens')) {
            return;
        }

        $this->iamRoot($menu)
            ->addChild('iam_api_tokens', ['route' => 'iam_admin_api_token_index'])
            ->setLabel('iam.api_token.index');
    }
}
