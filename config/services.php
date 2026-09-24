<?php

declare(strict_types=1);

use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\DomainEventPublisherInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\EventSerializerInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PasswordHasherInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PasswordPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineUserRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\ImmediateEventPublisher;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Listener\TablePrefixListener;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\ConfigurablePasswordPolicy;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\IamUserProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\RoleCatalog;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\SymfonyPasswordHasher;
use AlexandreBulete\DddIamBundle\Infrastructure\Serializer\JsonEventSerializer;
use AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Command\CreateSuperAdminCommand;
use AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Controller\AutocompleteUserByEmailAction;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $src = dirname(__DIR__) . '/src';

    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('AlexandreBulete\\DddIamBundle\\', $src . '/')
        ->exclude([
            // Domain classes are never services: registering aggregates and
            // value objects in the container is how a Domain starts depending
            // on the framework that instantiates it.
            $src . '/Domain',
            $src . '/DddIamBundle.php',

            // Doctrine instantiates DBAL types itself, from doctrine.dbal.types.
            $src . '/Infrastructure/Doctrine/Type',
            $src . '/Infrastructure/Doctrine/Mapping',

            // Conditional layers — see services_audit.php / services_admin.php.
            $src . '/Application/Subscriber',
            $src . '/Application/Query/FindAuditLogs',
            $src . '/Infrastructure/Doctrine/DoctrineAuditLogEntryRepository.php',
            $src . '/Infrastructure/Sylius',
        ]);

    // ── Ports → adapters ────────────────────────────────────────────────────
    // The one place where the hexagon is visible. Swapping any of these is how
    // a project changes behaviour without touching the bundle.
    $services->alias(PasswordHasherInterface::class, SymfonyPasswordHasher::class);
    $services->alias(PasswordPolicyInterface::class, ConfigurablePasswordPolicy::class);
    $services->alias(RoleCatalogInterface::class, RoleCatalog::class);
    $services->alias(EventSerializerInterface::class, JsonEventSerializer::class);
    $services->alias(DomainEventPublisherInterface::class, ImmediateEventPublisher::class);
    $services->alias(UserRepositoryInterface::class, DoctrineUserRepository::class);

    // ── Config-driven services ──────────────────────────────────────────────
    $services->set(RoleCatalog::class)
        ->args([
            param('iam.role_names'),
            param('iam.default_roles'),
        ]);

    $services->set(ConfigurablePasswordPolicy::class)
        ->args([
            param('iam.password_policy.min_length'),
            param('iam.password_policy.require_letters'),
            param('iam.password_policy.require_digits'),
            param('iam.password_policy.require_mixed_case'),
            param('iam.password_policy.require_special_character'),
        ]);

    $services->set(DoctrineUserRepository::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service(DomainEventPublisherInterface::class),
            param('iam.user_class'),
        ]);

    $services->set(CreateSuperAdminCommand::class)
        ->args([
            service(\AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface::class),
            service(UserRepositoryInterface::class),
            service(RoleCatalogInterface::class),
            param('iam.super_admin_role'),
        ]);

    $services->set(TablePrefixListener::class)
        ->args([
            param('iam.table_prefix'),
            param('iam.tables'),
        ])
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);

    // Plain invokable action, not an AbstractController subclass: autoconfigure
    // would not tag it, and an untagged controller cannot receive its arguments.
    $services->set(AutocompleteUserByEmailAction::class)
        ->tag('controller.service_arguments');

    // Referenced by id from security.yaml (`providers: { id: … }`).
    $services->set(IamUserProvider::class);
};
