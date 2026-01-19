<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Cache;

/**
 * Service for logging sync progress to cache for real-time UI updates.
 *
 * Stores structured log entries that can be polled by Livewire components
 * to show live progress during sync operations.
 */
class SyncOutputLogger
{
    /**
     * Log levels with their display properties.
     */
    public const LEVELS = [
        'info' => ['icon' => 'info-circle', 'color' => 'info'],
        'success' => ['icon' => 'check-circle', 'color' => 'success'],
        'warning' => ['icon' => 'exclamation-triangle', 'color' => 'warning'],
        'error' => ['icon' => 'x-circle', 'color' => 'error'],
    ];

    /**
     * Cache TTL in seconds (30 minutes).
     */
    private const CACHE_TTL = 1800;

    /**
     * Maximum number of log entries to keep.
     */
    private const MAX_ENTRIES = 500;

    private string $sessionKey;

    private string $cacheKeyLogs;

    private string $cacheKeyState;

    public function __construct(?string $sessionKey = null)
    {
        $this->sessionKey = $sessionKey ?? 'default';
        $this->cacheKeyLogs = "sync_output:{$this->sessionKey}:logs";
        $this->cacheKeyState = "sync_output:{$this->sessionKey}:state";
    }

    /**
     * Start a new sync session.
     */
    public function start(string $description = 'Starting sync...'): void
    {
        $this->clear();

        Cache::put($this->cacheKeyState, [
            'status' => 'running',
            'started_at' => now()->toIso8601String(),
            'description' => $description,
            'current_item' => 0,
            'total_items' => 0,
            'current_operation' => $description,
            'error_count' => 0,
        ], self::CACHE_TTL);

        $this->info($description);
    }

    /**
     * Log an info message.
     */
    public function info(string $message): void
    {
        $this->log('info', $message);
    }

    /**
     * Log a success message.
     */
    public function success(string $message): void
    {
        $this->log('success', $message);
    }

    /**
     * Log a warning message.
     */
    public function warning(string $message): void
    {
        $this->log('warning', $message);
    }

    /**
     * Log an error message.
     */
    public function error(string $message): void
    {
        $this->log('error', $message);
        $this->incrementErrorCount();
    }

    /**
     * Update the current progress.
     */
    public function progress(int $current, int $total, string $operation = ''): void
    {
        $state = $this->getState();
        $state['current_item'] = $current;
        $state['total_items'] = $total;

        if ($operation) {
            $state['current_operation'] = $operation;
        }

        Cache::put($this->cacheKeyState, $state, self::CACHE_TTL);
    }

    /**
     * Set the current operation description.
     */
    public function setOperation(string $operation): void
    {
        $state = $this->getState();
        $state['current_operation'] = $operation;
        Cache::put($this->cacheKeyState, $state, self::CACHE_TTL);
    }

    /**
     * Mark the sync as completed.
     */
    public function complete(string $message = 'Sync completed successfully.'): void
    {
        $state = $this->getState();
        $state['status'] = 'completed';
        $state['completed_at'] = now()->toIso8601String();
        $state['current_operation'] = $message;
        Cache::put($this->cacheKeyState, $state, self::CACHE_TTL);

        $this->success($message);
    }

    /**
     * Mark the sync as failed.
     */
    public function fail(string $message = 'Sync failed.'): void
    {
        $state = $this->getState();
        $state['status'] = 'failed';
        $state['completed_at'] = now()->toIso8601String();
        $state['current_operation'] = $message;
        Cache::put($this->cacheKeyState, $state, self::CACHE_TTL);

        $this->error($message);
    }

    /**
     * Get all log entries.
     *
     * @return array<int, array{timestamp: string, level: string, message: string}>
     */
    public function getLogs(): array
    {
        return Cache::get($this->cacheKeyLogs, []);
    }

    /**
     * Get logs since a specific index (for polling).
     *
     * @return array<int, array{timestamp: string, level: string, message: string}>
     */
    public function getLogsSince(int $index): array
    {
        $logs = $this->getLogs();

        return array_slice($logs, $index);
    }

    /**
     * Get the current state.
     *
     * @return array{status: string, started_at: ?string, completed_at: ?string, description: string, current_item: int, total_items: int, current_operation: string, error_count: int}
     */
    public function getState(): array
    {
        return Cache::get($this->cacheKeyState, [
            'status' => 'idle',
            'started_at' => null,
            'completed_at' => null,
            'description' => '',
            'current_item' => 0,
            'total_items' => 0,
            'current_operation' => '',
            'error_count' => 0,
        ]);
    }

    /**
     * Get combined output (state + logs) for UI rendering.
     *
     * @return array{state: array, logs: array, log_count: int}
     */
    public function getOutput(): array
    {
        $logs = $this->getLogs();

        return [
            'state' => $this->getState(),
            'logs' => $logs,
            'log_count' => count($logs),
        ];
    }

    /**
     * Check if a sync is currently running.
     */
    public function isRunning(): bool
    {
        return $this->getState()['status'] === 'running';
    }

    /**
     * Clear all logs and state.
     */
    public function clear(): void
    {
        Cache::forget($this->cacheKeyLogs);
        Cache::forget($this->cacheKeyState);
    }

    /**
     * Create a logger for a specific sync log ID.
     */
    public static function forSyncLog(int $syncLogId): static
    {
        return new static("sync_log_{$syncLogId}");
    }

    /**
     * Create a logger for a specific user session.
     */
    public static function forUser(int $userId): static
    {
        return new static("user_{$userId}");
    }

    /**
     * Add a log entry.
     */
    private function log(string $level, string $message): void
    {
        $logs = $this->getLogs();

        $logs[] = [
            'timestamp' => now()->format('H:i:s'),
            'level' => $level,
            'message' => $message,
        ];

        // Keep only the last MAX_ENTRIES
        if (count($logs) > self::MAX_ENTRIES) {
            $logs = array_slice($logs, -self::MAX_ENTRIES);
        }

        Cache::put($this->cacheKeyLogs, $logs, self::CACHE_TTL);
    }

    /**
     * Increment the error count.
     */
    private function incrementErrorCount(): void
    {
        $state = $this->getState();
        $state['error_count'] = ($state['error_count'] ?? 0) + 1;
        Cache::put($this->cacheKeyState, $state, self::CACHE_TTL);
    }
}
