<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Provider;

use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddIamBundle\Application\Query\FindRoleDefinition\FindRoleDefinitionQuery;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleDefinitionId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\RoleDefinitionResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Context\Option\RequestOption;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProviderInterface;

final readonly class RoleDefinitionItemProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function provide(Operation $operation, Context $context): ?RoleDefinitionResource
    {
        $id = $context->get(RequestOption::class)?->request()->attributes->getString('id');
        if ($id === null || $id === '') {
            return null;
        }

        return RoleDefinitionResource::fromModel(
            $this->queryBus->ask(new FindRoleDefinitionQuery(RoleDefinitionId::fromString($id))),
        );
    }
}
