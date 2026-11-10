<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Papastro\Pastroutine\{Channel, DeadlockException, Mutex, Pastroutine, Scheduler, WaitGroup};

final class PastroutineTest extends TestCase
{
    protected function setUp(): void
    {
        Scheduler::reset();
    }

    public function test_go_returns_result(): void
    {
        $p = pastroutine(fn (int $a, int $b) => $a + $b, 2, 3);
        $this->assertSame(5, $p->wait());
        $this->assertSame(Pastroutine::DONE, $p->status());
    }

    public function test_sleeping_tasks_run_concurrently(): void
    {
        $start = microtime(true);
        $results = Pastroutine::all([
            'a' => function () { Pastroutine::sleep(0.2); return 'A'; },
            'b' => function () { Pastroutine::sleep(0.2); return 'B'; },
            'c' => function () { Pastroutine::sleep(0.2); return 'C'; },
        ]);
        $elapsed = microtime(true) - $start;

        $this->assertSame(['a' => 'A', 'b' => 'B', 'c' => 'C'], $results);
        $this->assertLessThan(0.45, $elapsed, 'three 200ms sleeps must overlap (took ' . round($elapsed, 3) . 's)');
    }

    public function test_exception_propagates_through_wait(): void
    {
        $p = pastroutine(function () { throw new \DomainException('boom'); });
        $this->expectException(\DomainException::class);
        $p->wait();
    }

    public function test_unbuffered_channel_rendezvous(): void
    {
        $ch = new Channel();
        pastroutine(function () use ($ch) {
            foreach ([1, 2, 3] as $n) {
                $ch->send($n);
            }
            $ch->close();
        });

        $got = [];
        foreach ($ch as $v) {
            $got[] = $v;
        }
        $this->assertSame([1, 2, 3], $got);
    }

    public function test_buffered_channel_does_not_block_until_full(): void
    {
        $ch = new Channel(2);
        $ch->send('x');
        $ch->send('y');
        $this->assertCount(2, $ch);
        $this->assertSame('x', $ch->recv());
        $this->assertSame('y', $ch->recv());
    }

    public function test_closed_channel_reports_drained(): void
    {
        $ch = new Channel(1);
        $ch->send('last');
        $ch->close();
        $this->assertSame([true, 'last'], $ch->recvWithStatus());
        $this->assertSame([false, null], $ch->recvWithStatus());
        $this->expectException(\RuntimeException::class);
        $ch->send('nope');
    }

    public function test_worker_pool_with_waitgroup(): void
    {
        $jobs = new Channel(10);
        $out = new Channel(10);
        $wg = new WaitGroup();

        for ($w = 0; $w < 3; $w++) {
            $wg->add();
            pastroutine(function () use ($jobs, $out, $wg) {
                foreach ($jobs as $n) {
                    Pastroutine::sleep(0.01);
                    $out->send($n * $n);
                }
                $wg->done();
            });
        }
        foreach (range(1, 9) as $n) {
            $jobs->send($n);
        }
        $jobs->close();
        $wg->wait();
        $out->close();

        $squares = iterator_to_array($out, false);
        sort($squares);
        $this->assertSame([1, 4, 9, 16, 25, 36, 49, 64, 81], $squares);
    }

    public function test_mutex_serialises_critical_sections(): void
    {
        $mutex = new Mutex();
        $log = [];
        $wg = new WaitGroup();
        foreach (['a', 'b'] as $name) {
            $wg->add();
            pastroutine(function () use ($mutex, &$log, $name, $wg) {
                $mutex->synchronized(function () use (&$log, $name) {
                    $log[] = "$name-start";
                    Pastroutine::sleep(0.02);   // yields while holding the lock
                    $log[] = "$name-end";
                });
                $wg->done();
            });
        }
        $wg->wait();
        $this->assertSame(['a-start', 'a-end', 'b-start', 'b-end'], $log);
    }

    public function test_deadlock_is_detected_instead_of_hanging(): void
    {
        $ch = new Channel();
        $this->expectException(DeadlockException::class);
        $ch->recv(); // nobody will ever send
    }

    public function test_many_pastroutines_are_cheap(): void
    {
        $wg = new WaitGroup();
        $sum = 0;
        for ($i = 1; $i <= 5000; $i++) {
            $wg->add();
            pastroutine(function () use ($i, &$sum, $wg) {
                Pastroutine::yield();
                $sum += $i;
                $wg->done();
            });
        }
        $wg->wait();
        $this->assertSame(5000 * 5001 / 2, $sum);
    }

    public function test_parallel_runs_in_separate_processes(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl not available');
        }
        $parent = getmypid();
        $res = Pastroutine::parallel([
            fn () => [getmypid(), 6 * 7],
            fn () => [getmypid(), strtoupper('papastro')],
        ]);
        $this->assertSame(42, $res[0][1]);
        $this->assertSame('PAPASTRO', $res[1][1]);
        $this->assertNotSame($parent, $res[0][0]);
        $this->assertNotSame($res[0][0], $res[1][0]);
    }

    public function test_parallel_reports_child_failure(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl not available');
        }
        $this->expectExceptionMessage('exploded');
        Pastroutine::parallel([fn () => throw new \LogicException('exploded')]);
    }
}
