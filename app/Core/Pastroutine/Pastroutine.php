<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Pastroutine;

use Fiber;
use Throwable;

/**
 * Pastroutine — a lightweight concurrent task (Go's goroutine, with a Laravel-friendly API).
 *
 *   $p = pastroutine(function () { Pastroutine::sleep(0.1); return 'done'; });
 *   $p->wait();               // 'done'
 *
 *   Pastroutine::all([fn() => fetchA(), fn() => fetchB()]);   // run concurrently, collect results
 *   Pastroutine::parallel([fn() => heavy1(), fn() => heavy2()]); // real multi-core (fork)
 *
 * Cooperative: a Pastroutine runs until it sleeps, yields, waits on a Channel/WaitGroup/Mutex or
 * a stream. Thousands can run in one worker with a few KB each.
 */
final class Pastroutine implements Waitable
{
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const WAITING = 'waiting';
    public const DONE = 'done';
    public const FAILED = 'failed';

    private static int $counter = 0;

    public readonly int $id;
    private Fiber $fiber;
    private array $args;
    private string $status = self::PENDING;
    private mixed $result = null;
    private ?Throwable $error = null;
    private mixed $resumeValue = null;
    private bool $started = false;
    private bool $handled = false;
    /** @var callable|null */
    private $errorHandler = null;
    /** @var callable|null */
    private $successHandler = null;
    /** @var Waitable[] */
    private array $joiners = [];

    public function __construct(callable $task, array $args = [], private readonly Scheduler $scheduler = new Scheduler())
    {
        $this->id = ++self::$counter;
        $this->args = $args;
        $this->fiber = new Fiber($task(...));
    }

    // ------------------------------------------------------------------
    // Static, Go-style helpers
    // ------------------------------------------------------------------

    /** Spawn a Pastroutine on the current scheduler. */
    public static function go(callable $task, mixed ...$args): self
    {
        return Scheduler::current()->go($task, ...$args);
    }

    /** Pause the current Pastroutine (or the main script, running others meanwhile). */
    public static function sleep(float $seconds): void
    {
        Scheduler::current()->sleep($seconds);
    }

    /** Let other Pastroutines run, then continue. */
    public static function yield(): void
    {
        Scheduler::current()->yield();
    }

    /** Run every pending Pastroutine to completion. */
    public static function run(): void
    {
        Scheduler::current()->run();
    }

    /** Suspend until $stream is readable (non-blocking I/O). */
    public static function waitReadable($stream): void
    {
        Scheduler::current()->waitReadable($stream);
    }

    /**
     * Run callables concurrently and return their results in order.
     * First failure is rethrown after all tasks settled.
     *
     * @param array<int|string, callable> $tasks
     */
    public static function all(array $tasks): array
    {
        $running = [];
        foreach ($tasks as $key => $task) {
            $running[$key] = self::go($task);
        }
        $results = [];
        $firstError = null;
        foreach ($running as $key => $p) {
            try {
                $results[$key] = $p->wait();
            } catch (Throwable $e) {
                $firstError ??= $e;
            }
        }
        if ($firstError) {
            throw $firstError;
        }
        return $results;
    }

    /**
     * Run callables in separate OS processes (true multi-core parallelism).
     * Results must be serialisable. Falls back to sequential execution without pcntl.
     *
     * @param array<int|string, callable> $tasks
     */
    public static function parallel(array $tasks, int $maxProcesses = 8, float $timeout = 60.0): array
    {
        return Parallel::run($tasks, $maxProcesses, $timeout);
    }

    // ------------------------------------------------------------------
    // Instance API
    // ------------------------------------------------------------------

    /** Block until finished and return the result (rethrows the task's exception). */
    public function wait(): mixed
    {
        if (!$this->isFinished()) {
            $this->joiners[] = $waiter = $this->scheduler->currentWaiter();
            $this->scheduler->park($waiter);
        }
        $this->handled = true;
        if ($this->error) {
            throw $this->error;
        }
        return $this->result;
    }

    public function result(): mixed
    {
        return $this->result;
    }

    public function error(): ?Throwable
    {
        $this->handled = true;
        return $this->error;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isFinished(): bool
    {
        return $this->status === self::DONE || $this->status === self::FAILED;
    }

    public function isHandled(): bool
    {
        return $this->handled || !empty($this->joiners) || $this->errorHandler !== null;
    }

    /** Register an error callback if the pastroutine throws an exception. */
    public function catch(callable $callback): self
    {
        $this->errorHandler = $callback;
        $this->handled = true;
        if ($this->status === self::FAILED && $this->error !== null) {
            ($this->errorHandler)($this->error);
        }
        return $this;
    }

    /** Register a success callback, with optional error fallback. */
    public function then(callable $onSuccess, ?callable $onFailure = null): self
    {
        $this->successHandler = $onSuccess;
        if ($onFailure !== null) {
            $this->catch($onFailure);
        }
        if ($this->status === self::DONE) {
            ($this->successHandler)($this->result);
        }
        return $this;
    }

    // ------------------------------------------------------------------
    // Scheduler-facing internals
    // ------------------------------------------------------------------

    public function wake(mixed $value = null): void
    {
        $this->resumeValue = $value;
        $this->scheduler->enqueue($this);
    }

    /** @internal returns the command the fiber suspended with, or null when it finished */
    public function step(): mixed
    {
        $this->status = self::RUNNING;
        try {
            if (!$this->started) {
                $this->started = true;
                $command = $this->fiber->start(...$this->args);
            } else {
                $value = $this->resumeValue;
                $this->resumeValue = null;
                $command = $this->fiber->resume($value);
            }
        } catch (Throwable $e) {
            $this->error = $e;
            $this->status = self::FAILED;
            if ($this->errorHandler !== null) {
                $this->handled = true;
                ($this->errorHandler)($e);
            }
            $this->releaseJoiners();
            return null;
        }

        if ($this->fiber->isTerminated()) {
            $this->result = $this->fiber->getReturn();
            $this->status = self::DONE;
            if ($this->successHandler !== null) {
                ($this->successHandler)($this->result);
            }
            $this->releaseJoiners();
            return null;
        }

        $this->status = self::WAITING;
        return $command;
    }

    private function releaseJoiners(): void
    {
        foreach ($this->joiners as $joiner) {
            $joiner->wake();
        }
        $this->joiners = [];
    }
}
