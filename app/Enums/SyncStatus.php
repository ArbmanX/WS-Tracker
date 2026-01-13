<?php

namespace App\Enums;

enum SyncStatus: string
{
    case Started = 'started';
    case Completed = 'completed';
    case Failed = 'failed';
    case Warning = 'warning';

    /**
     * Get display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Started => 'Started',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Warning => 'Warning',
        };
    }

    /**
     * Get DaisyUI color class.
     */
    public function color(): string
    {
        return match ($this) {
            self::Started => 'info',
            self::Completed => 'success',
            self::Failed => 'error',
            self::Warning => 'warning',
        };
    }
}
