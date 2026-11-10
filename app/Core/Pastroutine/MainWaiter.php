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
 * Represents the main (non-fiber) script while it blocks on a Pastroutine primitive.
 * The scheduler keeps running other Pastroutines until this waiter is woken.
 */
final class MainWaiter implements Waitable
{
    public bool $woken = false;
    public mixed $value = null;

    public function wake(mixed $value = null): void
    {
        $this->woken = true;
        $this->value = $value;
    }
}
