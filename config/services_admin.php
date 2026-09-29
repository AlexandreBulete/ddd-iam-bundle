<?php

declare(strict_types=1);

use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu\AgentMenuContributor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu\ApiTokenMenuContributor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\AgentGrid;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\AgentGridProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\ApiTokenGrid;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\ApiTokenGridProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\CreateAgentProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\UpdateAgentProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\DeleteAgentProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\IssueApiTokenProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\RevokeApiTokenProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\AgentItemProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\ApiTokenItemProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu\RoleMenuContributor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Admin\Menu\UserMenuContributor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\RoleDefinitionGrid;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid\RoleDefinitionGridProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\CreateRoleDefinitionProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\DeleteRoleDefinitionProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor\UpdateRoleDefinitionProcessor;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider\RoleDefinitionItemProvider;
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

    $services->set(RoleDefinitionGridProvider::class);
    $services->set(RoleDefinitionGrid::class)->args([param('iam.admin.grid_limits')]);
    $services->set(RoleDefinitionItemProvider::class);
    $services->set(CreateRoleDefinitionProcessor::class);
    $services->set(UpdateRoleDefinitionProcessor::class);
    $services->set(DeleteRoleDefinitionProcessor::class);
    $services->set(RoleMenuContributor::class);

    $services->set(AgentGridProvider::class);
    $services->set(AgentGrid::class)->args([param('iam.admin.grid_limits')]);
    $services->set(AgentItemProvider::class);
    $services->set(CreateAgentProcessor::class);
    $services->set(UpdateAgentProcessor::class);
    $services->set(DeleteAgentProcessor::class);
    $services->set(AgentMenuContributor::class);

    $services->set(ApiTokenGridProvider::class);
    $services->set(ApiTokenGrid::class)->args([param('iam.admin.grid_limits')]);
    $services->set(ApiTokenItemProvider::class);
    $services->set(IssueApiTokenProcessor::class);
    $services->set(RevokeApiTokenProcessor::class);
    $services->set(ApiTokenMenuContributor::class);
};
