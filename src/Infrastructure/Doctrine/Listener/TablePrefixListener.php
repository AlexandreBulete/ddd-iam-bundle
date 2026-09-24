<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Doctrine\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Applies `iam.table_prefix` to the bundle's own tables at mapping-load time.
 *
 * A bundle cannot ship a configurable table name in XML — the file is static.
 * Rewriting the metadata as it is loaded is the standard way out, and it is
 * what makes this bundle installable in a database that already has a `user`
 * or `audit_log` table it does not own.
 *
 * Scoped to the two entities listed in the constructor: it must never touch a
 * project's own mapping, so it matches on the class name rather than on a
 * naming pattern.
 */
final readonly class TablePrefixListener
{
    /**
     * @param array<class-string, string> $tables entity FQCN => unprefixed table name
     */
    public function __construct(
        private string $prefix,
        private array $tables,
    ) {}

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $metadata = $args->getClassMetadata();
        $name = $metadata->getName();

        if (!isset($this->tables[$name])) {
            return;
        }

        // Inherited mappings are configured on the root entity only; touching a
        // child would rename the parent's table a second time.
        if ($metadata->isInheritanceTypeSingleTable() && !$metadata->isRootEntity()) {
            return;
        }

        $metadata->setPrimaryTable(['name' => $this->prefix . $this->tables[$name]]);
    }
}
