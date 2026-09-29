<?php

declare(strict_types=1);

use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Audit\Grid\AuditLogEntryGrid;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Audit\Grid\AuditLogEntryGridProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Audit\Menu\AuditLogMenuContributor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

/**
 * The audit log screen: loaded only when both `iam.admin.enabled` and
 * `iam.audit.enabled` are true. With the audit off, its query has no handler —
 * a screen, a menu entry or a route left behind would answer 500.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(AuditLogEntryGridProvider::class);
    $services->set(AuditLogEntryGrid::class)->args([param('iam.admin.grid_limits')]);
    $services->set(AuditLogMenuContributor::class);
};
