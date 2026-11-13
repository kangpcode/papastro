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
 * Anything that can be parked and later woken by the scheduler
 * (a Pastroutine, or the main script blocking on a channel / WaitGroup).
 */
interface Waitable
{
    public function wake(mixed $value = null): void;
}
