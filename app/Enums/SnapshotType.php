<?php

namespace App\Enums;

enum SnapshotType: string
{
    case Daily = 'daily';
    case StatusChange = 'status_change';
    case Manual = 'manual';

    /**
     * Get display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Daily Snapshot',
            self::StatusChange => 'Status Change',
            self::Manual => 'Manual Snapshot',
        };
    }
}
