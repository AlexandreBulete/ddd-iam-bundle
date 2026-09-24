<?php

declare(strict_types=1);

use AlexandreBulete\DddIamBundle\Application\Query\FindAuditLogs\FindAuditLogsHandler;
use AlexandreBulete\DddIamBundle\Application\Subscriber\AuditLogger;
use AlexandreBulete\DddIamBundle\Domain\Repository\AuditLogEntryRepositoryInterface;
use AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\DoctrineAuditLogEntryRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Loaded only when `iam.audit.enabled` is true.
 *
 * The audit trail is a real cost — a row and a flush per state change — and a
 * deployment that does not need it should not pay it. Keeping the services in
 * a separate file means "disabled" really means absent, not merely inert.
 *
 * The iam_audit_log TABLE is still mapped either way: dropping a table because
 * a flag flipped would destroy history that may still be legally required.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(DoctrineAuditLogEntryRepository::class);
    $services->alias(AuditLogEntryRepositoryInterface::class, DoctrineAuditLogEntryRepository::class);

    $services->set(FindAuditLogsHandler::class);
    $services->set(AuditLogger::class);
};
