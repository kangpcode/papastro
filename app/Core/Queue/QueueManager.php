<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v2.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Queue;

use Papastro\Foundation\Application;

/**
 * Base Job class for background queueable tasks.
 */
abstract class Job
{
    public int $tries = 3;
    public int $timeout = 60;

    abstract public function handle(): void;
}

/**
 * QueueManager — Dispatches and processes background jobs for high-scale applications.
 */
class QueueManager
{
    protected Application $app;
    protected string $driver;
    protected string $queueFile;
    protected string $failedFile;

    public function __construct(Application $app, ?string $driver = null)
    {
        $this->app = $app;
        $this->driver = $driver ?? (string) env('QUEUE_CONNECTION', 'file');
        $this->queueFile = $app->storagePath('cache/queue_jobs.json');
        $this->failedFile = $app->storagePath('cache/failed_jobs.json');
    }

    /**
     * Dispatch a job onto the queue or execute immediately if sync.
     */
    public function dispatch(object $job, string $queue = 'default'): string
    {
        $id = bin2hex(random_bytes(8));

        if ($this->driver === 'sync') {
            $this->executeJob($job);
            return $id;
        }

        $jobs = $this->readJobs($this->queueFile);
        $jobs[] = [
            'id' => $id,
            'queue' => $queue,
            'class' => get_class($job),
            'payload' => base64_encode(serialize($job)),
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->writeJobs($this->queueFile, $jobs);
        return $id;
    }

    /**
     * Pop and process the next available job on the queue.
     *
     * @return array|null Summary of processed job or null if empty
     */
    public function processNext(string $queue = 'default'): ?array
    {
        $jobs = $this->readJobs($this->queueFile);
        if (empty($jobs)) {
            return null;
        }

        $selectedIndex = null;
        foreach ($jobs as $idx => $item) {
            if (($item['queue'] ?? 'default') === $queue) {
                $selectedIndex = $idx;
                break;
            }
        }

        if ($selectedIndex === null) {
            return null;
        }

        $jobRecord = $jobs[$selectedIndex];
        array_splice($jobs, $selectedIndex, 1);
        $this->writeJobs($this->queueFile, $jobs);

        $jobRecord['attempts'] = ($jobRecord['attempts'] ?? 0) + 1;
        $start = microtime(true);

        try {
            $jobInstance = unserialize(base64_decode($jobRecord['payload']));
            $this->executeJob($jobInstance);

            return [
                'id' => $jobRecord['id'],
                'class' => $jobRecord['class'],
                'status' => 'processed',
                'elapsed_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $failed = $this->readJobs($this->failedFile);
            $jobRecord['error'] = $e->getMessage();
            $jobRecord['failed_at'] = date('Y-m-d H:i:s');
            $failed[] = $jobRecord;
            $this->writeJobs($this->failedFile, $failed);

            return [
                'id' => $jobRecord['id'],
                'class' => $jobRecord['class'],
                'status' => 'failed',
                'error' => $e->getMessage(),
                'elapsed_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        }
    }

    /**
     * Execute a job instance through the IoC container.
     */
    protected function executeJob(object $job): void
    {
        if (method_exists($job, 'handle')) {
            $this->app->call([$job, 'handle']);
        } elseif (is_callable($job)) {
            $this->app->call($job);
        }
    }

    /**
     * Get the number of pending jobs.
     */
    public function count(string $queue = 'default'): int
    {
        $jobs = $this->readJobs($this->queueFile);
        return count(array_filter($jobs, fn($j) => ($j['queue'] ?? 'default') === $queue));
    }

    /**
     * Get all failed jobs.
     */
    public function failed(): array
    {
        return $this->readJobs($this->failedFile);
    }

    /**
     * Retry a failed job by ID.
     */
    public function retry(string $id): bool
    {
        $failed = $this->readJobs($this->failedFile);
        $foundIndex = null;

        foreach ($failed as $idx => $job) {
            if ($job['id'] === $id) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === null) {
            return false;
        }

        $job = $failed[$foundIndex];
        array_splice($failed, $foundIndex, 1);
        $this->writeJobs($this->failedFile, $failed);

        unset($job['error'], $job['failed_at']);
        $job['attempts'] = 0;

        $jobs = $this->readJobs($this->queueFile);
        $jobs[] = $job;
        $this->writeJobs($this->queueFile, $jobs);

        return true;
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAll(): int
    {
        $failed = $this->readJobs($this->failedFile);
        if (empty($failed)) {
            return 0;
        }

        $count = count($failed);
        $jobs = $this->readJobs($this->queueFile);

        foreach ($failed as $job) {
            unset($job['error'], $job['failed_at']);
            $job['attempts'] = 0;
            $jobs[] = $job;
        }

        $this->writeJobs($this->queueFile, $jobs);
        $this->writeJobs($this->failedFile, []);

        return $count;
    }

    /**
     * Clear all pending jobs.
     */
    public function clear(): int
    {
        $jobs = $this->readJobs($this->queueFile);
        $count = count($jobs);
        $this->writeJobs($this->queueFile, []);
        return $count;
    }

    protected function readJobs(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        $content = @file_get_contents($file);
        if (!$content) {
            return [];
        }
        return json_decode($content, true) ?: [];
    }

    protected function writeJobs(string $file, array $jobs): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, json_encode(array_values($jobs), JSON_PRETTY_PRINT), LOCK_EX);
    }
}
