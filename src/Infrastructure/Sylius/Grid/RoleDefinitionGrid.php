<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid;

use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\RoleDefinitionResource;
use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\Action\UpdateAction;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\MainActionGroup;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Grid\AbstractGrid;
use Sylius\Bundle\GridBundle\Grid\ResourceAwareGridInterface;
use Sylius\Component\Grid\Builder\GridBuilderInterface;

class RoleDefinitionGrid extends AbstractGrid implements ResourceAwareGridInterface
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
            ->setProvider(RoleDefinitionGridProvider::class)
            ->setLimits($this->limits)
            ->orderBy('label', 'asc')
            ->addField(StringField::create('label')->setLabel('iam.role.label')->setSortable(true))
            ->addField(StringField::create('name')->setLabel('iam.role.name'))
            ->addField(StringField::create('permissionsSummary')->setLabel('iam.role.permissions'))
            ->addActionGroup(MainActionGroup::create(CreateAction::create()))
            ->addActionGroup(ItemActionGroup::create(UpdateAction::create(), DeleteAction::create()))
        ;
    }

    public function getResourceClass(): string
    {
        return RoleDefinitionResource::class;
    }
}
