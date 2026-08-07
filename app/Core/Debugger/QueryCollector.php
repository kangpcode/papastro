<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Debugger;

/**
 * QueryCollector — tracks all executed database queries for the Debugbar.
 */
class QueryCollector
{
    protected array $queries = [];

    /**
     * Record a query execution.
     */
    public function record(string $sql, array $bindings, float $elapsed): void
    {
        $this->queries[] = [
            'sql'      => $sql,
            'bindings' => $bindings,
            'elapsed'  => round($elapsed * 1000, 2), // Convert to ms
            'time'     => microtime(true),
            'n1'       => $this->detectN1($sql),
        ];
    }

    /**
     * Detect potential N+1 queries by checking for repeated query patterns.
     */
    protected function detectN1(string $sql): bool
    {
        $stripped = preg_replace('/\s+/', ' ', trim($sql));
        $count    = 0;

        foreach ($this->queries as $q) {
            $existing = preg_replace('/\s+/', ' ', trim($q['sql']));
            if (preg_replace('/\d+/', '?', $existing) === preg_replace('/\d+/', '?', $stripped)) {
                $count++;
            }
        }

        return $count >= 5; // Flag if same pattern runs 5+ times
    }

    /**
     * Get all recorded queries.
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Get total number of queries.
     */
    public function count(): int
    {
        return count($this->queries);
    }

    /**
     * Get total query time in milliseconds.
     */
    public function totalTime(): float
    {
        return array_sum(array_column($this->queries, 'elapsed'));
    }

    /**
     * Get queries flagged as potential N+1.
     */
    public function getNPlusOneQueries(): array
    {
        return array_filter($this->queries, fn ($q) => $q['n1']);
    }
}
