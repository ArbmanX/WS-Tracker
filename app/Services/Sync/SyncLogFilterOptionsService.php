<?php

namespace App\Services\Sync;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;

class SyncLogFilterOptionsService
{
    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return collect(SyncStatus::cases())
            ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public function triggerOptions(): array
    {
        return collect(SyncTrigger::cases())
            ->mapWithKeys(fn ($trigger) => [$trigger->value => $trigger->label()])
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public function typeOptions(): array
    {
        return collect(SyncType::cases())
            ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
            ->toArray();
    }
}
