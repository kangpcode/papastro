<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v5.0.0
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
 * Scheduler — cooperative scheduler that multiplexes Pastroutines on one thread
 * (timers + stream readiness + FIFO run queue, like a tiny Go runtime / event loop).
 */
final class Scheduler
{
    private static ?Scheduler $current = null;

    /** @var \SplQueue<Pastroutine> */
    private \SplQueue $ready;
    /** @var array<int, array{0: float, 1: Pastroutine}> */
    private array $sleepers = [];
    /** @var array<int, array{0: resource, 1: Pastroutine}> */
    private array $readers = [];
    private ?Pastroutine $running = null;
    /** @var Throwable[] */
    private array $unhandled = [];
    private int $spawned = 0;

    public function __construct()
    {
        $this->ready = new \SplQueue();
    }

    public static function current(): self
    {
        return self::$current ??= new self();
    }

    /** Replace the process scheduler (tests / fresh worker). */
    public static function reset(): self
    {
        return self::$current = new self();
    }

    public function go(callable $task, mixed ...$args): Pastroutine
    {
        $p = new Pastroutine($task, $args, $this);
        $this->spawned++;
        $this->ready->enqueue($p);
        return $p;
    }

    public function enqueue(Pastroutine $p): void
    {
        $this->ready->enqueue($p);
    }

    public function spawnedCount(): int
    {
        return $this->spawned;
    }

    /** Who is calling? The running Pastroutine, or a MainWaiter for the main script. */
    public function currentWaiter(): Waitable
    {
        return $this->running !== null && Fiber::getCurrent() !== null ? $this->running : new MainWaiter();
    }

    /**
     * Park the caller until $waiter is woken. Returns the wake value.
     * From a Pastroutine: suspends the fiber. From main: drives the scheduler.
     */
    public function park(Waitable $waiter): mixed
    {
        if ($waiter instanceof MainWaiter) {
            while (!$waiter->woken) {
                if (!$this->tick()) {
                    throw new DeadlockException('all pastroutines are asleep - deadlock!');
                }
            }
            return $waiter->value;
        }

        return Fiber::suspend(['block']);
    }

    public function sleep(float $seconds): void
    {
        $until = microtime(true) + max(0.0, $seconds);

        if ($this->inPastroutine()) {
            Fiber::suspend(['sleep', $until]);
            return;
        }

        // Main script: keep other Pastroutines running while we wait.
        while (microtime(true) < $until) {
            if (!$this->tick($until)) {
                $left = $until - microtime(true);
                if ($left > 0) {
                    usleep((int) ($left * 1_000_000));
                }
                return;
            }
        }
    }

    public function yield(): void
    {
        if ($this->inPastroutine()) {
            Fiber::suspend(['yield']);
        } else {
            $this->tick();
        }
    }

    public function waitReadable($stream): void
    {
        if ($this->inPastroutine()) {
            Fiber::suspend(['read', $stream]);
            return;
        }
        $r = [$stream];
        $w = $e = null;
        @stream_select($r, $w, $e, null);
    }

    /** Run until nothing is runnable, sleeping or waiting on I/O. */
    public function run(): void
    {
        while ($this->tick()) {
            // keep going
        }
        if ($this->unhandled) {
            $first = array_shift($this->unhandled);
            $this->unhandled = [];
            throw $first;
        }
    }

    public function pending(): int
    {
        return count($this->ready) + count($this->sleepers) + count($this->readers);
    }

    private function inPastroutine(): bool
    {
        return $this->running !== null && Fiber::getCurrent() !== null;
    }

    /**
     * One scheduler step. Returns false when there is nothing left that could make progress.
     * @param float|null $deadline do not block past this timestamp
     */
    private function tick(?float $deadline = null): bool
    {
        $this->wakeDueSleepers();

        if ($this->ready->isEmpty()) {
            if (!$this->sleepers && !$this->readers) {
                return false;
            }
            $this->waitForEvents($deadline);
        }

        $batch = $this->ready->count();
        for ($i = 0; $i < $batch && !$this->ready->isEmpty(); $i++) {
            /** @var Pastroutine $p */
            $p = $this->ready->dequeue();
            $previous = $this->running;
            $this->running = $p;
            $command = $p->step();
            $this->running = $previous;

            if ($command === null) {
                if ($p->status() === Pastroutine::FAILED && $p->error() !== null) {
                    $this->reportFailure($p);
                }
                continue;
            }

            match ($command[0] ?? 'block') {
                'yield' => $this->ready->enqueue($p),
                'sleep' => $this->sleepers[] = [$command[1], $p],
                'read'  => $this->readers[] = [$command[1], $p],
                default => null, // blocked on a primitive; woken explicitly
            };
        }

        return true;
    }

    private function reportFailure(Pastroutine $p): void
    {
        // A failure is handled if someone joined it or attached an error handler.
        if ($p->isHandled()) {
            return;
        }

        $e = $p->error();
        $this->unhandled[] = $e;
        if (count($this->unhandled) > 100) {
            array_shift($this->unhandled);
        }
        if (function_exists('app') && \Papastro\Foundation\Application::getInstance()->bound(\Papastro\Log\Logger::class)) {
            \Papastro\Foundation\Application::getInstance()->make(\Papastro\Log\Logger::class)
                ->error('Pastroutine #' . $p->id . ' unhandled error: ' . $e->getMessage());
        }
    }

    private function wakeDueSleepers(): void
    {
        if (!$this->sleepers) {
            return;
        }
        $now = microtime(true);
        foreach ($this->sleepers as $i => [$due, $p]) {
            if ($due <= $now) {
                unset($this->sleepers[$i]);
                $this->ready->enqueue($p);
            }
        }
    }

    private function waitForEvents(?float $deadline): void
    {
        $timeout = null;
        if ($this->sleepers) {
            $next = min(array_map(static fn ($s) => $s[0], $this->sleepers));
            $timeout = max(0.0, $next - microtime(true));
        }
        if ($deadline !== null) {
            $left = max(0.0, $deadline - microtime(true));
            $timeout = $timeout === null ? $left : min($timeout, $left);
        }

        if ($this->readers) {
            $read = array_map(static fn ($r) => $r[0], $this->readers);
            $w = $e = null;
            $sec = $timeout === null ? null : (int) floor($timeout);
            $usec = $timeout === null ? 0 : (int) (($timeout - floor($timeout)) * 1_000_000);
            if (@stream_select($read, $w, $e, $sec, $usec) > 0) {
                foreach ($this->readers as $i => [$stream, $p]) {
                    if (in_array($stream, $read, true)) {
                        unset($this->readers[$i]);
                        $this->ready->enqueue($p);
                    }
                }
            }
        } elseif ($timeout !== null && $timeout > 0) {
            usleep((int) ($timeout * 1_000_000));
        }

        $this->wakeDueSleepers();
    }
}
