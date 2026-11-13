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

/**
 * WaitGroup — wait for a set of Pastroutines to finish (Go's sync.WaitGroup).
 *
 *   $wg = new WaitGroup();
 *   foreach ($urls as $u) { $wg->add(); go(function () use ($u, $wg) { fetch($u); $wg->done(); }); }
 *   $wg->wait();
 */
final class WaitGroup
{
    private int $count = 0;
    /** @var Waitable[] */
    private array $waiters = [];

    public function add(int $delta = 1): void
    {
        $this->count += $delta;
        if ($this->count < 0) {
            throw new \LogicException('negative WaitGroup counter');
        }
        $this->releaseIfDone();
    }

    public function done(): void
    {
        $this->add(-1);
    }

    public function wait(): void
    {
        if ($this->count === 0) {
            return;
        }
        $scheduler = Scheduler::current();
        $this->waiters[] = $waiter = $scheduler->currentWaiter();
        $scheduler->park($waiter);
    }

    public function pending(): int
    {
        return $this->count;
    }

    private function releaseIfDone(): void
    {
        if ($this->count === 0) {
            foreach ($this->waiters as $w) {
                $w->wake();
            }
            $this->waiters = [];
        }
    }
}
