<?php

declare(strict_types=1);

use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Migrations\Version20260928120000;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

/**
 * Loaded only when DoctrineMigrationsBundle is enabled.
 *
 * Each migration is a service whose id is its class name: that is what
 * DoctrineMigrationsBundle's service migration factory looks up, and the class
 * name is also the version recorded in the migrations table. The tag hands them
 * to the bundle, which binds the Connection and the logger; the table prefix is
 * the one thing only we can provide.
 *
 * The prefix is an explicit argument, not a `bind()`: DoctrineMigrationsBundle's
 * RegisterMigrationsPass calls setBindings() on every tagged migration, which
 * replaces any binding declared here.
 *
 * A new migration is added to this list — it is never picked up by scanning a
 * directory, so a stray class cannot turn into a schema change.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->tag('doctrine_migrations.migration');

    foreach ([Version20260928120000::class] as $migration) {
        $services->set($migration)
            ->arg('$tablePrefix', param('iam.table_prefix'));
    }
};
