<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Pastroutine;

/**
 * Mutex — mutual exclusion across Pastroutines (only matters when a critical section yields).
 */
final class Mutex
{
    private bool $locked = false;
    /** @var Waitable[] */
    private array $queue = [];

    public function lock(): void
    {
        if (!$this->locked) {
            $this->locked = true;
            return;
        }
        $scheduler = Scheduler::current();
        $this->queue[] = $waiter = $scheduler->currentWaiter();
        $scheduler->park($waiter); // ownership is handed over by unlock()
    }

    public function unlock(): void
    {
        if (!$this->locked) {
            throw new \LogicException('unlock of unlocked mutex');
        }
        if ($this->queue) {
            array_shift($this->queue)->wake(); // stays locked, new owner
            return;
        }
        $this->locked = false;
    }

    public function synchronized(callable $fn): mixed
    {
        $this->lock();
        try {
            return $fn();
        } finally {
            $this->unlock();
        }
    }
}
