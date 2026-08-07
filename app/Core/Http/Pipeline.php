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

namespace Papastro\Http;

use Closure;

/**
 * Middleware Pipeline — passes request through a stack of middleware.
 */
class Pipeline
{
    protected mixed $container;
    protected mixed $passable;
    protected array $pipes = [];

    public function __construct(mixed $container)
    {
        $this->container = $container;
    }

    public function send(mixed $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    public function through(array $pipes): static
    {
        $this->pipes = $pipes;
        return $this;
    }

    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $destination
        );

        return $pipeline($this->passable);
    }

    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_string($pipe)) {
                    $pipe = $this->container->make($pipe);
                }

                if ($pipe instanceof Closure) {
                    return $pipe($passable, $stack);
                }

                return $pipe->handle($passable, $stack);
            };
        };
    }
}
