<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Unit\Infrastructure\Sylius;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;

/**
 * Both buses answer with the same model, and the command bus remembers what
 * it was asked to do: enough to see which use cases a form submission implies.
 */
final class RecordingBuses implements CommandBusInterface, QueryBusInterface
{
    /** @var list<class-string> */
    public private(set) array $dispatched = [];

    public function __construct(
        private readonly object $model,
    ) {}

    public function dispatch(CommandInterface $command): mixed
    {
        $this->dispatched[] = $command::class;

        // A fake answering every use case with one model cannot honour each
        // command's result type; the tests only send commands that return it.
        return $this->model; // @phpstan-ignore return.type
    }

    public function ask(QueryInterface $query): mixed
    {
        return $this->model; // @phpstan-ignore return.type (same as dispatch())
    }
}
