<?php

declare(strict_types=1);

use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\ApiTokenRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\DomainEventPublisherInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\EventSerializerInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\GrantPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\IdentityGeneratorInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PasswordHasherInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PasswordPolicyInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\PermissionCatalogInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\SecretGeneratorInterface;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineAgentRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineApiTokenRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineRoleDefinitionRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineUserRepository;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\ImmediateEventPublisher;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Listener\TablePrefixListener;
use AlexandreBulete\DddIamBundle\Infrastructure\Identity\UlidIdentityGenerator;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\ConfigurablePasswordPolicy;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\DoctrineRoleCatalog;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\IamPermissionChecker;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\IamUserProvider;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\RandomSecretGenerator;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\RegistryPermissionCatalog;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\SecurityGrantPolicy;
use AlexandreBulete\DddIamBundle\Infrastructure\Security\SymfonyPasswordHasher;
use AlexandreBulete\DddIamBundle\Infrastructure\Serializer\JsonEventSerializer;
use AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Controller\AutocompleteUserByEmailAction;
use AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Controller\WhoAmIAction;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionCheckerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $src = dirname(__DIR__) . '/src';

    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        // Issuing a token and reading one both need the prefix (ADR 0011).
        ->bind('string $tokenPrefix', param('iam.api_tokens.prefix'));

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

            // Conditional layers — see services_audit.php / services_admin.php
            // / services_migrations.php.
            $src . '/Application/Subscriber',
            $src . '/Application/Query/FindAuditLogs',
            $src . '/Infrastructure/Doctrine/DoctrineAuditLogEntryRepository.php',
            $src . '/Infrastructure/Doctrine/Migrations',
            $src . '/Infrastructure/Sylius',
        ]);

    // ── Ports → adapters ────────────────────────────────────────────────────
    // The one place where the hexagon is visible. Swapping any of these is how
    // a project changes behaviour without touching the bundle.
    $services->alias(PasswordHasherInterface::class, SymfonyPasswordHasher::class);
    $services->alias(PasswordPolicyInterface::class, ConfigurablePasswordPolicy::class);
    $services->alias(RoleCatalogInterface::class, DoctrineRoleCatalog::class);
    $services->alias(RoleDefinitionRepositoryInterface::class, DoctrineRoleDefinitionRepository::class);
    $services->alias(PermissionCatalogInterface::class, RegistryPermissionCatalog::class);
    $services->alias(GrantPolicyInterface::class, SecurityGrantPolicy::class);

    // Providing a checker is what turns authorization on (ADR 0008):
    // ddd-symfony-bundle then installs its middleware on the buses.
    $services->alias(PermissionCheckerInterface::class, IamPermissionChecker::class);
    $services->alias(EventSerializerInterface::class, JsonEventSerializer::class);
    $services->alias(DomainEventPublisherInterface::class, ImmediateEventPublisher::class);
    $services->alias(UserRepositoryInterface::class, DoctrineUserRepository::class);
    $services->alias(IdentityGeneratorInterface::class, UlidIdentityGenerator::class);
    $services->alias(AgentRepositoryInterface::class, DoctrineAgentRepository::class);
    $services->alias(ApiTokenRepositoryInterface::class, DoctrineApiTokenRepository::class);
    $services->alias(SecretGeneratorInterface::class, RandomSecretGenerator::class);

    // ── Config-driven services ──────────────────────────────────────────────
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
    $services->set(WhoAmIAction::class)
        ->tag('controller.service_arguments');

    // Referenced by id from security.yaml (`providers: { id: … }`).
    $services->set(IamUserProvider::class);
};
