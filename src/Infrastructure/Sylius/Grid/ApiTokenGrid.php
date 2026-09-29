<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Grid;

use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\ApiTokenResource;
use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\MainActionGroup;
use Sylius\Bundle\GridBundle\Builder\Field\DateTimeField;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Builder\Field\TwigField;
use Sylius\Bundle\GridBundle\Grid\AbstractGrid;
use Sylius\Bundle\GridBundle\Grid\ResourceAwareGridInterface;
use Sylius\Component\Grid\Builder\GridBuilderInterface;

class ApiTokenGrid extends AbstractGrid implements ResourceAwareGridInterface
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
            ->setProvider(ApiTokenGridProvider::class)
            ->setLimits($this->limits)
            ->orderBy('issuedAt', 'desc')
            ->addField(StringField::create('label')->setLabel('iam.api_token.label'))
            ->addField(StringField::create('agentName')->setLabel('iam.api_token.agent'))
            ->addField(TwigField::create('state', '@DddIam/admin/grid/api_token_state.html.twig')->setLabel('iam.api_token.state'))
            ->addField(DateTimeField::create('issuedAt')->setLabel('iam.api_token.issued_at')->setSortable(true))
            ->addField(DateTimeField::create('expiresAt')->setLabel('iam.api_token.expires_at')->setSortable(true))
            ->addField(DateTimeField::create('lastUsedAt')->setLabel('iam.api_token.last_used_at'))
            ->addField(StringField::create('id')->setLabel('iam.api_token.id'))
            ->addActionGroup(MainActionGroup::create(CreateAction::create()))
            ->addActionGroup(ItemActionGroup::create(DeleteAction::create()->setLabel('iam.api_token.revoke')))
        ;
    }

    public function getResourceClass(): string
    {
        return ApiTokenResource::class;
    }
}
