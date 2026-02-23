<?php

namespace App\Services\Sync;

use App\Models\SyncLog;
use Illuminate\Database\Eloquent\Builder;

class SyncLogQueryService
{
    /**
     * Build a filtered sync log query with common eager loads.
     */
    public function filtered(
        ?string $status = null,
        ?string $trigger = null,
        ?string $type = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): Builder {
        return SyncLog::query()
            ->with(['triggeredBy', 'region'])
            ->when($status, fn ($q) => $q->where('sync_status', $status))
            ->when($trigger, fn ($q) => $q->where('sync_trigger', $trigger))
            ->when($type, fn ($q) => $q->where('sync_type', $type))
            ->when($dateFrom, fn ($q) => $q->where('started_at', '>=', $dateFrom.' 00:00:00'))
            ->when($dateTo, fn ($q) => $q->where('started_at', '<=', $dateTo.' 23:59:59'));
    }
}
