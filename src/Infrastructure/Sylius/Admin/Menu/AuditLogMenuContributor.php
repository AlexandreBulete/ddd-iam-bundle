<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Registered only when `iam.audit.enabled` is true — a menu entry pointing at
 * a route that does not exist would break the whole back-office layout, not
 * just this link.
 */
#[AutoconfigureTag('app.menu_contributor', ['priority' => 20])]
final readonly class AuditLogMenuContributor extends IamMenuContributor
{
    public function contribute(ItemInterface $menu): void
    {
        $this->iamRoot($menu)
            ->addChild('iam_audit_log', ['route' => 'iam_admin_audit_log_entry_index'])
            ->setLabel('iam.audit.index');
    }
}
