<?php

namespace App\Enums;

enum SyncType: string
{
    case CircuitList = 'circuit_list';
    case Aggregates = 'aggregates';
    case Full = 'full';

    /**
     * Get display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CircuitList => 'Circuit List',
            self::Aggregates => 'Aggregates',
            self::Full => 'Full Sync',
        };
    }
}
