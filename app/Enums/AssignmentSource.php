<?php

namespace App\Enums;

enum AssignmentSource: string
{
    case ApiSync = 'api_sync';
    case Manual = 'manual';

    /**
     * Get display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ApiSync => 'API Sync',
            self::Manual => 'Manual',
        };
    }
}
