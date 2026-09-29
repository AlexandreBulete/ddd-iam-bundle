<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Sylius\State\Processor;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\IssueApiToken\IssueApiTokenCommand;
use AlexandreBulete\DddIamBundle\Application\Command\IssueApiToken\IssuedApiToken;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\AgentId;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\ApiTokenResource;
use Psr\Clock\ClockInterface;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Webmozart\Assert\Assert;

/**
 * Issues the token and shows it once (ADR 0011): as a flash message, read on
 * the next page and dropped from the session as it is displayed. Nothing
 * else ever holds it in clear.
 */
final readonly class IssueApiTokenProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ClockInterface $clock,
        private RequestStack $requests,
    ) {}

    public function process(mixed $data, Operation $operation, Context $context): ApiTokenResource
    {
        Assert::isInstanceOf($data, ApiTokenResource::class);
        Assert::stringNotEmpty($data->agentId);
        Assert::stringNotEmpty($data->label);

        /** @var IssuedApiToken $issued */
        $issued = $this->commandBus->dispatch(new IssueApiTokenCommand(
            agentId: AgentId::fromString($data->agentId),
            label: $data->label,
            expiresAt: $this->clock->now()->modify(sprintf('+%d days', $data->lifetimeDays)),
        ));

        $session = $this->requests->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('warning', [
                'message' => 'iam.api_token.issued',
                'parameters' => ['%token%' => $issued->plain->toString()],
            ]);
        }

        return ApiTokenResource::fromModel($issued->token, (string) $data->agentName, $this->clock->now());
    }
}
