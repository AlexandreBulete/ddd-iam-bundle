<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.menu_contributor', ['priority' => 12])]
final readonly class AgentMenuContributor extends IamMenuContributor
{
    public function contribute(ItemInterface $menu): void
    {
        if (!$this->allowed('iam.find_agents')) {
            return;
        }

        $this->iamRoot($menu)
            ->addChild('iam_agents', ['route' => 'iam_admin_agent_index'])
            ->setLabel('iam.agent.index');
    }
}
