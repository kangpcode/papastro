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

use IteratorAggregate;
use Traversable;

/**
 * Channel — typed-by-convention message pipe between Pastroutines (Go's chan).
 *
 *   $ch = new Channel(10);       // buffered; new Channel() = unbuffered rendezvous
 *   go(fn() => $ch->send('hi'));
 *   $ch->recv();                 // 'hi'
 *   $ch->close();
 *   foreach ($ch as $msg) {}     // drains until closed
 */
final class Channel implements IteratorAggregate, \Countable
{
    private array $buffer = [];
    /** @var Waitable[] */
    private array $receivers = [];
    /** @var array<int, array{0: Waitable, 1: mixed}> */
    private array $senders = [];
    private bool $closed = false;

    public function __construct(private readonly int $capacity = 0)
    {
    }

    public function send(mixed $value): void
    {
        if ($this->closed) {
            throw new \RuntimeException('send on closed channel');
        }

        // A receiver is already waiting → hand the value over directly.
        if ($this->receivers) {
            array_shift($this->receivers)->wake([true, $value]);
            return;
        }

        if (count($this->buffer) < $this->capacity) {
            $this->buffer[] = $value;
            return;
        }

        // Full / unbuffered: park until a receiver takes it.
        $scheduler = Scheduler::current();
        $waiter = $scheduler->currentWaiter();
        $this->senders[] = [$waiter, $value];
        $result = $scheduler->park($waiter);
        if ($result === 'closed') {
            throw new \RuntimeException('send on closed channel');
        }
    }

    /** Receive a value; returns null when the channel is closed and drained. */
    public function recv(): mixed
    {
        [, $value] = $this->recvWithStatus();
        return $value;
    }

    /** @return array{0: bool, 1: mixed} [ok, value] — ok=false means closed & drained */
    public function recvWithStatus(): array
    {
        if ($this->buffer) {
            $value = array_shift($this->buffer);
            if ($this->senders) { // a parked sender can now fill the freed slot
                [$sender, $sent] = array_shift($this->senders);
                $this->buffer[] = $sent;
                $sender->wake('sent');
            }
            return [true, $value];
        }

        if ($this->senders) { // unbuffered rendezvous
            [$sender, $sent] = array_shift($this->senders);
            $sender->wake('sent');
            return [true, $sent];
        }

        if ($this->closed) {
            return [false, null];
        }

        $scheduler = Scheduler::current();
        $waiter = $scheduler->currentWaiter();
        $this->receivers[] = $waiter;
        $result = $scheduler->park($waiter);

        return is_array($result) ? $result : [false, null];
    }

    /** Non-blocking receive. @return array{0: bool, 1: mixed} */
    public function tryRecv(): array
    {
        if ($this->buffer || $this->senders) {
            return $this->recvWithStatus();
        }
        return [false, null];
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        foreach ($this->receivers as $r) {
            $r->wake([false, null]);
        }
        $this->receivers = [];
        foreach ($this->senders as [$s]) {
            $s->wake('closed');
        }
        $this->senders = [];
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function count(): int
    {
        return count($this->buffer);
    }

    public function getIterator(): Traversable
    {
        while (true) {
            [$ok, $value] = $this->recvWithStatus();
            if (!$ok) {
                return;
            }
            yield $value;
        }
    }
}
