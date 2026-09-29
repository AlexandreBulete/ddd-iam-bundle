<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\ChangeAgentRoles\ChangeAgentRolesCommand;
use AlexandreBulete\DddIamBundle\Application\Command\DescribeAgent\DescribeAgentCommand;
use AlexandreBulete\DddIamBundle\Application\Command\ReactivateAgent\ReactivateAgentCommand;
use AlexandreBulete\DddIamBundle\Application\Command\RevokeAgent\RevokeAgentCommand;
use AlexandreBulete\DddIamBundle\Application\Command\SuspendAgent\SuspendAgentCommand;
use AlexandreBulete\DddIamBundle\Application\Query\FindAgent\FindAgentQuery;
use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Model\Agent;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\AgentResource;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Symfony\Component\Uid\Ulid;
use Webmozart\Assert\Assert;

/**
 * Only what changed becomes a command — see UpdateUserProcessor: each use
 * case is authorized and journaled on its own.
 */
final readonly class UpdateAgentProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): AgentResource
    {
        Assert::isInstanceOf($data, AgentResource::class);
        Assert::isInstanceOf($data->id, Ulid::class);
        Assert::stringNotEmpty($data->name);

        $id = AgentId::fromUlid($data->id);
        /** @var Agent $agent */
        $agent = $this->queryBus->ask(new FindAgentQuery($id));

        if ($data->name !== $agent->name || $data->description !== $agent->description) {
            /** @var Agent $agent */
            $agent = $this->commandBus->dispatch(new DescribeAgentCommand($id, $data->name, $data->description));
        }

        $roles = RoleSet::fromNames($data->roles);
        if (!$roles->equals($agent->roles)) {
            /** @var Agent $agent */
            $agent = $this->commandBus->dispatch(new ChangeAgentRolesCommand($id, $roles));
        }

        if ($data->status !== null && $data->status !== $agent->status->toEnum()) {
            /** @var Agent $agent */
            $agent = $this->commandBus->dispatch(match ($data->status) {
                UserStatusEnum::ACTIVE => new ReactivateAgentCommand($id),
                UserStatusEnum::SUSPENDED => new SuspendAgentCommand($id),
                UserStatusEnum::REVOKED => new RevokeAgentCommand($id),
            });
        }

        return AgentResource::fromModel($agent);
    }
}
