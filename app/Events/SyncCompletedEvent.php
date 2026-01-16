<?php

namespace App\Events;

use App\Models\SyncLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a sync operation completes successfully.
 */
class SyncCompletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public SyncLog $syncLog
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
        return 'sync.completed';
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
            'sync_status' => $this->syncLog->sync_status->value,
            'circuits_processed' => $this->syncLog->circuits_processed,
            'circuits_created' => $this->syncLog->circuits_created,
            'circuits_updated' => $this->syncLog->circuits_updated,
            'aggregates_created' => $this->syncLog->aggregates_created,
            'duration_seconds' => $this->syncLog->duration_seconds,
            'completed_at' => $this->syncLog->completed_at->toIso8601String(),
        ];
    }
}
