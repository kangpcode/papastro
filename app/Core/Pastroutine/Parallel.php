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
 * Parallel — true multi-core execution: each task runs in a forked process and returns its
 * (serialisable) result through a socket pair. Requires ext-pcntl; otherwise runs sequentially.
 */
final class Parallel
{
    /**
     * @param array<int|string, callable> $tasks
     */
    public static function run(array $tasks, int $maxProcesses = 8, float $timeout = 60.0): array
    {
        if (!function_exists('pcntl_fork') || !function_exists('posix_kill') || PHP_SAPI !== 'cli') {
            return self::sequential($tasks);
        }

        $results = [];
        $queue = $tasks;
        /** @var array<int|string, array{pid:int, sock:resource, buf:string, started:float}> $running */
        $running = [];
        $errors = [];

        // Children must not share (and later close) the parent's DB handle.
        if (function_exists('app') && \Papastro\Foundation\Application::getInstance()->bound(\Papastro\ORM\Connection::class)) {
            \Papastro\Foundation\Application::getInstance()->make(\Papastro\ORM\Connection::class)->disconnect();
        }

        while ($queue || $running) {
            while ($queue && count($running) < max(1, $maxProcesses)) {
                $key = array_key_first($queue);
                $task = $queue[$key];
                unset($queue[$key]);
                $running[$key] = self::spawn($task);
            }

            $read = array_map(static fn ($r) => $r['sock'], $running);
            $w = $e = null;
            if (@stream_select($read, $w, $e, 1) > 0) {
                foreach ($running as $key => &$r) {
                    if (in_array($r['sock'], $read, true)) {
                        $chunk = fread($r['sock'], 65536);
                        if ($chunk === '' || $chunk === false) {
                            self::finish($key, $r, $results, $errors);
                            unset($running[$key]);
                        } else {
                            $r['buf'] .= $chunk;
                        }
                    }
                }
                unset($r);
            }

            // Timeout enforcement
            foreach ($running as $key => $r) {
                if (microtime(true) - $r['started'] > $timeout) {
                    posix_kill($r['pid'], SIGKILL);
                    pcntl_waitpid($r['pid'], $status);
                    fclose($r['sock']);
                    $errors[$key] = new \RuntimeException("parallel task [{$key}] timed out after {$timeout}s");
                    unset($running[$key]);
                }
            }
        }

        if ($errors) {
            throw reset($errors);
        }

        // Preserve the caller's key order.
        $ordered = [];
        foreach (array_keys($tasks) as $key) {
            $ordered[$key] = $results[$key] ?? null;
        }
        return $ordered;
    }

    private static function spawn(callable $task): array
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($pair === false) {
            throw new \RuntimeException('Could not create socket pair for parallel task.');
        }

        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new \RuntimeException('pcntl_fork failed.');
        }

        if ($pid === 0) {
            // ---- child ----
            fclose($pair[0]);
            try {
                $payload = serialize(['ok' => true, 'value' => $task()]);
            } catch (\Throwable $e) {
                $payload = serialize(['ok' => false, 'class' => get_class($e), 'message' => $e->getMessage()]);
            }
            fwrite($pair[1], $payload);
            fflush($pair[1]);
            fclose($pair[1]);
            // SIGKILL ourselves: skips destructors/shutdown handlers that could close the parent's resources.
            posix_kill(posix_getpid(), SIGKILL);
            exit(0);
        }

        fclose($pair[1]);
        stream_set_blocking($pair[0], false);
        return ['pid' => $pid, 'sock' => $pair[0], 'buf' => '', 'started' => microtime(true)];
    }

    private static function finish(int|string $key, array $r, array &$results, array &$errors): void
    {
        fclose($r['sock']);
        pcntl_waitpid($r['pid'], $status);

        $data = $r['buf'] === '' ? null : @unserialize($r['buf']);
        if (!is_array($data)) {
            $errors[$key] = new \RuntimeException("parallel task [{$key}] died without a result");
            return;
        }
        if ($data['ok']) {
            $results[$key] = $data['value'];
        } else {
            $errors[$key] = new \RuntimeException("parallel task [{$key}] failed: [{$data['class']}] {$data['message']}");
        }
    }

    private static function sequential(array $tasks): array
    {
        $out = [];
        foreach ($tasks as $key => $task) {
            $out[$key] = $task();
        }
        return $out;
    }
}
