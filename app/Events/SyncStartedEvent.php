<?php

namespace App\Events;

use App\Models\SyncLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a sync operation begins.
 */
class SyncStartedEvent implements ShouldBroadcast
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
        return 'sync.started';
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
            'sync_trigger' => $this->syncLog->sync_trigger->value,
            'api_status_filter' => $this->syncLog->api_status_filter,
            'started_at' => $this->syncLog->started_at->toIso8601String(),
        ];
    }
}
