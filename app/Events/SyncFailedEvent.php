<?php

namespace App\Events;

use App\Models\SyncLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a sync operation fails.
 */
class SyncFailedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Throwable|null  $exception  The exception that caused the failure
     */
    public function __construct(
        public SyncLog $syncLog,
        public ?\Throwable $exception = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('dashboard'),
            new Channel('sync-status'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sync.failed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'sync_id' => $this->syncLog->id,
            'sync_type' => $this->syncLog->sync_type->value,
            'error_message' => $this->syncLog->error_message,
            'duration_seconds' => $this->syncLog->duration_seconds,
            'completed_at' => $this->syncLog->completed_at?->toIso8601String(),
        ];
    }
}
