<?php

declare(strict_types=1);

namespace Yume\Shared\Container;

/**
 * Small autowiring container.
 *
 * Deliberately not a full DI framework: it resolves constructor dependencies by
 * type-hint, honours explicit factories, and binds interfaces to implementations.
 * That is all a modular monolith needs, and it keeps the composition root readable.
 */
final class Container
{
    /** @var array<string, callable(self): mixed> */
    private array $factories = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /** @var list<string> guards against circular dependencies */
    private array $resolving = [];

    /** @param callable(self): mixed $factory */
    public function singleton(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /** Binds an interface (or any alias) to a concrete class name. */
    public function bind(string $abstract, string $concrete): void
    {
        $this->aliases[$abstract] = $concrete;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->factories[$id])
            || isset($this->aliases[$id])
            || class_exists($id);
    }

    /**
     * @template T of object
     * @param class-string<T>|string $id
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (in_array($id, $this->resolving, true)) {
            throw new ContainerException(sprintf(
                'Circular dependency detected while resolving "%s" (chain: %s).',
                $id,
                implode(' -> ', [...$this->resolving, $id]),
            ));
        }

        $this->resolving[] = $id;

        try {
            $resolved = $this->resolve($id);
        } finally {
            array_pop($this->resolving);
        }

        $this->instances[$id] = $resolved;

        return $resolved;
    }

    private function resolve(string $id): mixed
    {
        if (isset($this->factories[$id])) {
            return ($this->factories[$id])($this);
        }

        if (isset($this->aliases[$id])) {
            return $this->get($this->aliases[$id]);
        }

        if (!class_exists($id)) {
            throw new ContainerException(sprintf('Service "%s" is not registered and is not an instantiable class.', $id));
        }

        return $this->build($id);
    }

    /** @param class-string $class */
    private function build(string $class): object
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(sprintf('Class "%s" is not instantiable; bind it to a concrete implementation.', $class));
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($class, $parameter);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(string $class, \ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return $this->get($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($type instanceof \ReflectionNamedType && $type->allowsNull()) {
            return null;
        }

        throw new ContainerException(sprintf(
            'Cannot autowire parameter "$%s" of %s::__construct(); register an explicit factory.',
            $parameter->getName(),
            $class,
        ));
    }
}
