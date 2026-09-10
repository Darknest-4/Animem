<?php

declare(strict_types=1);

namespace Yume\Shared\Bus;

use Yume\Contracts\Bus\CommandBusInterface;
use Yume\Contracts\Bus\CommandInterface;
use Yume\Shared\Container\Container;

/**
 * Maps a command class to its handler class and dispatches through the container.
 *
 * The map is explicit (bootstrap/providers.php) rather than convention-based, so
 * an unregistered use case fails loudly at boot instead of silently at runtime.
 */
final class CommandBus implements CommandBusInterface
{
    /** @param array<class-string<CommandInterface>, class-string> $handlerMap */
    public function __construct(
        private readonly Container $container,
        private array $handlerMap = [],
    ) {
    }

    /** @param class-string<CommandInterface> $command @param class-string $handler */
    public function register(string $command, string $handler): void
    {
        $this->handlerMap[$command] = $handler;
    }

    public function dispatch(CommandInterface $command): mixed
    {
        $class = $command::class;

        if (!isset($this->handlerMap[$class])) {
            throw new \RuntimeException(sprintf('No handler registered for command "%s".', $class));
        }

        $handler = $this->container->get($this->handlerMap[$class]);

        if (!is_callable($handler)) {
            throw new \RuntimeException(sprintf('Handler "%s" must be invokable.', $this->handlerMap[$class]));
        }

        return $handler($command);
    }
}
