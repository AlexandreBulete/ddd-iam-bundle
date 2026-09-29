<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle;

use AlexandreBulete\DddIamBundle\Domain\Model\AuditLogEntry;
use AlexandreBulete\DddIamBundle\Domain\Model\RoleDefinition;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\AuditLogEntryIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\EmailType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\PasswordType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\PermissionSetType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\RoleDefinitionIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\RoleSetType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\RoleType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\UserIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\UserStatusType;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Identity & Access Management as a drop-in Bounded Context.
 *
 * Installing this bundle gives a project working authentication, a user CRUD
 * in the Sylius back office, an audit trail and a bootstrap CLI — without
 * copying a single file. Everything a project legitimately differs on is
 * configuration; see the README for the full seam list.
 *
 * @phpstan-type IamConfig array{
 *     user_class: class-string<User>,
 *     table_prefix: string,
 *     password_policy: array<string, int|bool>,
 *     audit: array{enabled: bool},
 *     admin: array{enabled: bool, grid_limits: list<int>},
 * }
 */
final class DddIamBundle extends AbstractBundle
{
    protected string $extensionAlias = 'iam';

    /** Entity FQCN => unprefixed table name, applied by TablePrefixListener. */
    private const TABLES = [
        User::class => 'user',
        AuditLogEntry::class => 'audit_log',
        RoleDefinition::class => 'role',
    ];

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('user_class')
                    ->defaultValue(User::class)
                    ->info('Subclass of the IAM User aggregate. Overriding it means shipping your own Doctrine mapping — prefer a companion aggregate keyed by UserId.')
                ->end()
                ->scalarNode('table_prefix')
                    ->defaultValue('iam_')
                    ->info('Prefix for this bundle\'s tables (iam_user, iam_audit_log).')
                ->end()
                ->arrayNode('password_policy')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('min_length')->defaultValue(12)->min(1)->end()
                        ->booleanNode('require_letters')->defaultTrue()->end()
                        ->booleanNode('require_digits')->defaultTrue()->end()
                        ->booleanNode('require_mixed_case')->defaultFalse()->end()
                        ->booleanNode('require_special_character')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('audit')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('admin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Sylius back-office CRUD. Turn off for an API-only or headless deployment.')
                        ->end()
                        ->arrayNode('grid_limits')
                            ->integerPrototype()->end()
                            ->defaultValue([10, 25, 50])
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // The shape is guaranteed by the tree in configure(): the Config
        // component has validated and defaulted every key by now.
        /** @var IamConfig $config */
        $parameters = $container->parameters()
            ->set('iam.user_class', $config['user_class'])
            ->set('iam.table_prefix', $config['table_prefix'])
            ->set('iam.tables', self::TABLES)
            ->set('iam.admin.grid_limits', $config['admin']['grid_limits']);

        // Flattened one key per rule: `param()` resolves a parameter name, it
        // does not walk into an array, so `iam.password_policy.min_length` has
        // to exist as a parameter in its own right.
        foreach ($config['password_policy'] as $rule => $value) {
            $parameters->set('iam.password_policy.' . $rule, $value);
        }

        $container->import($this->getPath() . '/config/services.php');

        // Both layers are opt-out rather than always-on: an audit logger that
        // writes to a table nobody wants, or a menu entry pointing at a route
        // that was never registered, are worse than a missing feature.
        if ($config['audit']['enabled']) {
            $container->import($this->getPath() . '/config/services_audit.php');
        }

        if ($config['admin']['enabled']) {
            $container->import($this->getPath() . '/config/services_admin.php');

            if ($config['audit']['enabled']) {
                $container->import($this->getPath() . '/config/services_admin_audit.php');
            }
        }

        if (self::migrationsEnabled($builder)) {
            $container->import($this->getPath() . '/config/services_migrations.php');
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var list<array<string, mixed>> $configs */
        $configs = $builder->getExtensionConfig($this->extensionAlias);

        // prependExtension() runs before the config tree is processed, so the
        // defaults are not applied yet — every value read here needs its own
        // fallback.
        $userClass = User::class;
        $adminEnabled = true;
        $auditEnabled = true;

        foreach ($configs as $config) {
            if (is_string($config['user_class'] ?? null)) {
                $userClass = $config['user_class'];
            }
            $admin = $config['admin'] ?? null;
            if (is_array($admin) && is_bool($admin['enabled'] ?? null)) {
                $adminEnabled = $admin['enabled'];
            }
            $audit = $config['audit'] ?? null;
            if (is_array($audit) && is_bool($audit['enabled'] ?? null)) {
                $auditEnabled = $audit['enabled'];
            }
        }

        $this->prependDoctrine($builder, $userClass);
        $this->prependMigrations($builder);
        $this->prependTranslator($builder);

        if ($adminEnabled) {
            $this->prependSyliusResources($builder, $auditEnabled);
        }
    }

    private function prependDoctrine(ContainerBuilder $builder, string $userClass): void
    {
        $builder->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    UserIdType::NAME => UserIdType::class,
                    EmailType::NAME => EmailType::class,
                    PasswordType::NAME => PasswordType::class,
                    UserStatusType::NAME => UserStatusType::class,
                    RoleSetType::NAME => RoleSetType::class,
                    RoleType::NAME => RoleType::class,
                    RoleDefinitionIdType::NAME => RoleDefinitionIdType::class,
                    PermissionSetType::NAME => PermissionSetType::class,
                    AuditLogEntryIdType::NAME => AuditLogEntryIdType::class,
                ],
            ],
        ]);

        // A project that subclassed the aggregate owns its mapping: ours names
        // the parent class, and registering both would map the same table twice.
        if ($userClass !== User::class) {
            $builder->prependExtensionConfig('doctrine', [
                'orm' => [
                    'mappings' => [
                        'IamAudit' => [
                            'type' => 'xml',
                            'is_bundle' => false,
                            'dir' => $this->getPath() . '/src/Infrastructure/Doctrine/Mapping',
                            'prefix' => 'AlexandreBulete\DddIamBundle\Domain\Model',
                            'alias' => 'IamAudit',
                        ],
                    ],
                ],
            ]);

            return;
        }

        $builder->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'Iam' => [
                        'type' => 'xml',
                        'is_bundle' => false,
                        'dir' => $this->getPath() . '/src/Infrastructure/Doctrine/Mapping',
                        'prefix' => 'AlexandreBulete\DddIamBundle\Domain\Model',
                        'alias' => 'Iam',
                    ],
                ],
            ],
        ]);
    }

    /**
     * The bundle ships its own migrations as services (they need the table
     * prefix), which DoctrineMigrationsBundle only looks up with this switch on.
     * It adds a lookup, it changes nothing for the project's file migrations.
     */
    private function prependMigrations(ContainerBuilder $builder): void
    {
        if (!self::migrationsEnabled($builder)) {
            return;
        }

        $builder->prependExtensionConfig('doctrine_migrations', ['enable_service_migrations' => true]);
    }

    /**
     * Read from `kernel.bundles`, not hasExtension(): inside loadExtension() the
     * builder is scoped to this extension alone, so hasExtension() would answer
     * false there even with DoctrineMigrationsBundle enabled.
     */
    private static function migrationsEnabled(ContainerBuilder $builder): bool
    {
        /** @var array<string, class-string> $bundles */
        $bundles = $builder->getParameter('kernel.bundles');

        return in_array(DoctrineMigrationsBundle::class, $bundles, true);
    }

    private function prependTranslator(ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('framework', [
            'translator' => [
                'paths' => [$this->getPath() . '/translations'],
            ],
        ]);
    }

    private function prependSyliusResources(ContainerBuilder $builder, bool $auditEnabled): void
    {
        // DddSyliusBundle only globs the application's own src/*/Infrastructure/
        // Sylius/Resource; a bundle has to declare its own paths. The audit log
        // resource only exists with the audit: without it, its route would 500.
        $paths = [$this->getPath() . '/src/Infrastructure/Sylius/Resource'];
        if ($auditEnabled) {
            $paths[] = $this->getPath() . '/src/Infrastructure/Sylius/Audit/Resource';
        }

        $builder->prependExtensionConfig('sylius_resource', [
            'mapping' => ['paths' => $paths],
        ]);
    }
}
