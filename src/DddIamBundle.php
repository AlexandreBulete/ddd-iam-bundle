<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle;

use AlexandreBulete\DddIamBundle\Domain\Model\AuditLogEntry;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\AuditLogEntryIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\EmailType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\PasswordType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\RoleSetType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\UserIdType;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Type\UserStatusType;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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
 *     roles: array<mixed>,
 *     default_roles: list<string>,
 *     super_admin_role: string,
 *     password_policy: array<string, int|bool>,
 *     audit: array{enabled: bool},
 *     admin: array{enabled: bool, grid_limits: list<int>},
 * }
 */
final class DddIamBundle extends AbstractBundle
{
    protected string $extensionAlias = 'iam';

    /**
     * Roles every deployment gets.
     *
     * NOT declared as `defaultValue()` in the config tree, and that is
     * deliberate: Symfony replaces a defaulted array wholesale as soon as the
     * user declares one key. A project adding `moderator` would silently lose
     * `user`, `admin` and `super_admin`. Merging here instead makes `iam.roles`
     * purely additive, which is what anyone writing it expects.
     */
    public const DEFAULT_ROLES = [
        'user' => ['inherits' => []],
        'admin' => ['inherits' => ['user']],
        'super_admin' => ['inherits' => ['admin']],
    ];

    /** Entity FQCN => unprefixed table name, applied by TablePrefixListener. */
    private const TABLES = [
        User::class => 'user',
        AuditLogEntry::class => 'audit_log',
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
                ->arrayNode('roles')
                    ->info('Project roles, merged on top of user/admin/super_admin. Key is the short name: `moderator` becomes ROLE_MODERATOR.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('inherits')
                                ->info('Roles this one implies, fed into security.role_hierarchy.')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('default_roles')
                    ->info('Granted to a user created without an explicit role set.')
                    ->scalarPrototype()->end()
                    ->defaultValue(['user'])
                ->end()
                ->scalarNode('super_admin_role')
                    ->defaultValue('super_admin')
                    ->info('Role granted by iam:create-super-admin.')
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
        $roles = self::mergeRoles($config['roles']);

        $parameters = $container->parameters()
            ->set('iam.user_class', $config['user_class'])
            ->set('iam.table_prefix', $config['table_prefix'])
            ->set('iam.tables', self::TABLES)
            ->set('iam.role_names', array_keys($roles))
            ->set('iam.default_roles', $config['default_roles'])
            ->set('iam.super_admin_role', $config['super_admin_role'])
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
        $declaredRoles = [];
        $userClass = User::class;
        $adminEnabled = true;

        foreach ($configs as $config) {
            if (is_array($config['roles'] ?? null)) {
                $declaredRoles = array_replace($declaredRoles, $config['roles']);
            }
            if (is_string($config['user_class'] ?? null)) {
                $userClass = $config['user_class'];
            }
            $admin = $config['admin'] ?? null;
            if (is_array($admin) && is_bool($admin['enabled'] ?? null)) {
                $adminEnabled = $admin['enabled'];
            }
        }

        $this->prependDoctrine($builder, $userClass);
        $this->prependMigrations($builder);
        $this->prependSecurity($builder, self::mergeRoles($declaredRoles));
        $this->prependTranslator($builder);

        if ($adminEnabled) {
            $this->prependSyliusResources($builder);
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

    /**
     * @param array<string, array{inherits: list<string>}> $roles
     */
    private function prependSecurity(ContainerBuilder $builder, array $roles): void
    {
        $hierarchy = [];

        foreach ($roles as $name => $definition) {
            $inherits = $definition['inherits'];
            if ($inherits === []) {
                continue;
            }

            $hierarchy[Role::fromName($name)->value()] = array_map(
                static fn (string $parent): string => Role::fromName($parent)->value(),
                $inherits,
            );
        }

        if ($hierarchy === []) {
            return;
        }

        // Prepended, so an application declaring its own role_hierarchy wins —
        // the bundle states a default, it does not impose one.
        $builder->prependExtensionConfig('security', ['role_hierarchy' => $hierarchy]);
    }

    private function prependTranslator(ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('framework', [
            'translator' => [
                'paths' => [$this->getPath() . '/translations'],
            ],
        ]);
    }

    private function prependSyliusResources(ContainerBuilder $builder): void
    {
        // DddSyliusBundle only globs the application's own src/*/Infrastructure/
        // Sylius/Resource; a bundle has to declare its own path.
        $builder->prependExtensionConfig('sylius_resource', [
            'mapping' => [
                'paths' => [$this->getPath() . '/src/Infrastructure/Sylius/Resource'],
            ],
        ]);
    }

    /**
     * Normalises the `iam.roles` node on top of DEFAULT_ROLES.
     *
     * Takes it raw: prependExtension() reads it before the config tree has
     * validated anything. A malformed entry is rejected rather than guessed.
     *
     * @param array<mixed> $declared
     *
     * @return array<string, array{inherits: list<string>}>
     */
    private static function mergeRoles(array $declared): array
    {
        $normalized = [];

        foreach ($declared as $name => $definition) {
            $inherits = is_array($definition) ? ($definition['inherits'] ?? []) : [];
            if (!is_string($name) || ($definition !== null && !is_array($definition)) || !is_array($inherits)) {
                throw new InvalidConfigurationException(sprintf('iam.roles: invalid definition for role "%s".', $name));
            }

            $parents = [];
            foreach ($inherits as $parent) {
                if (!is_string($parent)) {
                    throw new InvalidConfigurationException(sprintf('iam.roles.%s.inherits: role names must be strings.', $name));
                }
                $parents[] = $parent;
            }

            $normalized[$name] = ['inherits' => $parents];
        }

        return array_replace(self::DEFAULT_ROLES, $normalized);
    }
}
