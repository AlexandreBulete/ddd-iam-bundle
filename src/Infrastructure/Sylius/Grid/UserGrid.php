<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid;

use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\UserResource;
use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\Action\UpdateAction;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\BulkActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\MainActionGroup;
use Sylius\Bundle\GridBundle\Builder\Field\DateTimeField;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;
use Sylius\Component\Grid\Builder\GridBuilderInterface;
use Sylius\Bundle\GridBundle\Grid\AbstractGrid;
use Sylius\Bundle\GridBundle\Grid\ResourceAwareGridInterface;

/**
 * The user list in the back office.
 *
 * A project extending this grid (extra column, extra filter) decorates it —
 * see the bundle README. Page limits come from `iam.admin.grid_limits` so a
 * deployment can tune them without touching this class.
 */
class UserGrid extends AbstractGrid implements ResourceAwareGridInterface
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
            ->setProvider(UserGridProvider::class)
            ->setLimits($this->limits)
            ->orderBy('email', 'asc')
            ->addOrderBy('createdAt', 'desc')
            ->addFilter(StringFilter::create('email', ['email']))
            ->addFilter(StringFilter::create('firstName', ['first_name']))
            ->addFilter(StringFilter::create('lastName', ['last_name']))
            ->addField(
                StringField::create('email')
                    ->setLabel('iam.user.email')
                    ->setSortable(true),
            )
            ->addField(
                StringField::create('firstName')
                    ->setLabel('iam.user.first_name')
                    ->setSortable(true),
            )
            ->addField(
                StringField::create('lastName')
                    ->setLabel('iam.user.last_name')
                    ->setSortable(true),
            )
            ->addField(
                // Not sortable: the roles live in a JSON column, so ordering on
                // them would sort the serialized text, not anything meaningful.
                StringField::create('rolesLabel')
                    ->setLabel('iam.user.roles'),
            )
            ->addField(
                StringField::create('status')
                    // The DTO holds the enum case; `.value` is the backing
                    // string a table cell can actually render.
                    ->setPath('status.value')
                    ->setLabel('iam.user.status')
                    ->setSortable(true),
            )
            ->addField(
                DateTimeField::create('createdAt')
                    ->setLabel('sylius.ui.created_at')
                    ->setSortable(true),
            )
            ->addActionGroup(
                MainActionGroup::create(
                    CreateAction::create(),
                ),
            )
            ->addActionGroup(
                ItemActionGroup::create(
                    UpdateAction::create(),
                    DeleteAction::create(),
                ),
            )
            ->addActionGroup(
                BulkActionGroup::create(
                    DeleteAction::create(),
                ),
            )
        ;
    }

    public function getResourceClass(): string
    {
        return UserResource::class;
    }
}
