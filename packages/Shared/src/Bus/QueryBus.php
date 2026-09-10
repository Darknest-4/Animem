<?php

declare(strict_types=1);

namespace Yume\Shared\Bus;

use Yume\Contracts\Bus\QueryBusInterface;
use Yume\Contracts\Bus\QueryInterface;
use Yume\Shared\Container\Container;

final class QueryBus implements QueryBusInterface
{
    /** @param array<class-string<QueryInterface>, class-string> $handlerMap */
    public function __construct(
        private readonly Container $container,
        private array $handlerMap = [],
    ) {
    }

    /** @param class-string<QueryInterface> $query @param class-string $handler */
    public function register(string $query, string $handler): void
    {
        $this->handlerMap[$query] = $handler;
    }

    public function ask(QueryInterface $query): mixed
    {
        $class = $query::class;

        if (!isset($this->handlerMap[$class])) {
            throw new \RuntimeException(sprintf('No handler registered for query "%s".', $class));
        }

        $handler = $this->container->get($this->handlerMap[$class]);

        if (!is_callable($handler)) {
            throw new \RuntimeException(sprintf('Handler "%s" must be invokable.', $this->handlerMap[$class]));
        }

        return $handler($query);
    }
}
