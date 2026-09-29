<?php

declare(strict_types=1);

use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu\UserMenuContributor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\UserGrid;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\UserGridProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\CreateUserProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\DeleteUserProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\UpdateUserProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\UserBulkItemsProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\UserItemProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

/**
 * Loaded only when `iam.admin.enabled` is true — everything Sylius, and
 * nothing else. An API-only or headless deployment turns the flag off and the
 * grids, menu entries and CRUD state handlers simply do not exist. The audit
 * log screen is in services_admin_audit.php: it also needs the audit.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(UserGridProvider::class);

    $services->set(UserGrid::class)->args([param('iam.admin.grid_limits')]);

    $services->set(UserItemProvider::class);
    $services->set(UserBulkItemsProvider::class);
    $services->set(CreateUserProcessor::class);
    $services->set(UpdateUserProcessor::class);
    $services->set(DeleteUserProcessor::class);

    $services->set(UserMenuContributor::class);
};
