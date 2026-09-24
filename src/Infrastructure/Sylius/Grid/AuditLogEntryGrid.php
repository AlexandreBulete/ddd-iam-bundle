<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid;

use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\AuditLogEntryResource;
use Sylius\Bundle\GridBundle\Builder\Field\DateTimeField;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;
use Sylius\Component\Grid\Builder\GridBuilderInterface;
use Sylius\Bundle\GridBundle\Grid\AbstractGrid;
use Sylius\Bundle\GridBundle\Grid\ResourceAwareGridInterface;

/**
 * No action group at all — the audit log is read-only, and the absence of
 * create/update/delete here is the enforcement, not an oversight.
 */
class AuditLogEntryGrid extends AbstractGrid implements ResourceAwareGridInterface
{
    /**
     * @param list<int> $limits
     */
    public function __construct(
        private readonly array $limits,
    ) {}

    public static function getName(): string
    {
        return self::class;
    }

    public function buildGrid(GridBuilderInterface $gridBuilder): void
    {
        $gridBuilder
            ->setProvider(AuditLogEntryGridProvider::class)
            ->setLimits($this->limits)
            ->orderBy('occurredAt', 'desc')
            ->addFilter(StringFilter::create('eventType', ['event_type']))
            ->addFilter(StringFilter::create('userId', ['user_id']))
            ->addField(
                DateTimeField::create('occurredAt')
                    ->setLabel('iam.audit.occurred_at')
                    ->setSortable(true),
            )
            ->addField(
                StringField::create('eventTypeShort')
                    ->setLabel('iam.audit.event_type')
                    ->setSortable(true, 'eventType'),
            )
            ->addField(
                StringField::create('userId')
                    ->setLabel('iam.audit.user_id'),
            )
            ->addField(
                StringField::create('payload')
                    ->setLabel('iam.audit.payload'),
            )
        ;
    }

    public function getResourceClass(): string
    {
        return AuditLogEntryResource::class;
    }
}
