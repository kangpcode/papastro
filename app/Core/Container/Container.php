<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Papastro IoC Container
 *
 * A lightweight but powerful Inversion of Control (IoC) container
 * that supports constructor injection, singleton bindings, and
 * interface-to-concrete class resolution.
 */
class Container
{
    /**
     * The current globally available container instance.
     */
    protected static ?Container $instance = null;

    /**
     * The container's bindings.
     */
    protected array $bindings = [];

    /**
     * The container's shared instances (singletons).
     */
    protected array $instances = [];

    /**
     * The registered type aliases.
     */
    protected array $aliases = [];

    /**
     * The stack of concretions currently being built.
     */
    protected array $buildStack = [];

    /**
     * Get the globally available instance of the container.
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     */
    public static function setInstance(?Container $container = null): ?Container
    {
        return static::$instance = $container;
    }

    /**
     * Determine if the given abstract type has been bound.
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }

    /**
     * Determine if a given string is an alias.
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Register a binding with the container.
     */
    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            if (!is_string($concrete)) {
                throw new \InvalidArgumentException(
                    self::class . '::bind(): Argument #2 must be a Closure, string, or null.'
                );
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Register a shared binding (singleton) in the container.
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Alias a type to a different name.
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new \LogicException("[$abstract] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolve the given type from the container.
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     */
    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        // If an instance has already been resolved, return it
        if (isset($this->instances[$abstract]) && empty($parameters)) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        // Build the concrete type
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        // If the type is shared (singleton), cache it
        if ($this->isShared($abstract) && empty($parameters)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     */
    protected function getConcrete(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Determine if the given concrete is buildable.
     */
    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
     */
    public function build(mixed $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new \RuntimeException("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Target [$concrete] is not instantiable.");
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            array_pop($this->buildStack);
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        try {
            $instances = $this->resolveDependencies($dependencies, $parameters);
        } catch (\Exception $e) {
            array_pop($this->buildStack);
            throw $e;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->getName(), $parameters)) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            $result = is_null($this->getParameterType($dependency))
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     */
    protected function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isVariadic()) {
            return [];
        }

        throw new \RuntimeException(
            "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()?->getName()}"
        );
    }

    /**
     * Resolve a class based dependency from the container.
     */
    protected function resolveClass(ReflectionParameter $parameter): mixed
    {
        try {
            return $parameter->isVariadic()
                ? $this->resolveVariadicClass($parameter)
                : $this->make($this->getParameterType($parameter));
        } catch (\Exception $e) {
            if ($parameter->isDefaultValueAvailable()) {
                array_pop($this->buildStack);
                return $parameter->getDefaultValue();
            }

            if ($parameter->isVariadic()) {
                array_pop($this->buildStack);
                return [];
            }

            throw $e;
        }
    }

    /**
     * Resolve a class based variadic dependency from the container.
     */
    protected function resolveVariadicClass(ReflectionParameter $parameter): mixed
    {
        $className = $this->getParameterType($parameter);
        $abstract = $this->getAlias($className);

        if (!is_array($concrete = $this->getConcrete($abstract))) {
            return $this->make($className);
        }

        return array_map(fn ($c) => $this->build($c), $concrete);
    }

    /**
     * Get the type hint for the given parameter.
     */
    protected function getParameterType(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (!is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }
            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    /**
     * Get the alias for an abstract if available.
     */
    public function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * Get a closure to resolve the given type from the container.
     */
    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * Determine if a given type is shared (singleton).
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared']);
    }

    /**
     * Drop all of the stale instances and aliases.
     */
    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     */
    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed
    {
        if (is_string($callback) && !str_contains($callback, '::') && $defaultMethod) {
            $callback = [$this->make($callback), $defaultMethod];
        }

        if (is_array($callback)) {
            [$class, $method] = $callback;
            $reflector = new \ReflectionMethod($class, $method);
            $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);
            return $reflector->invokeArgs(is_object($class) ? $class : $this->make($class), $dependencies);
        }

        $reflector = new \ReflectionFunction(Closure::fromCallable($callback));
        $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);
        return $reflector->invokeArgs($dependencies);
    }

    /**
     * Determine if the container has a binding for the given abstract.
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    /**
     * Get the container bindings.
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
