<?php

namespace App\Enums;

enum SyncTrigger: string
{
    case Scheduled = 'scheduled';
    case Manual = 'manual';
    case WorkflowEvent = 'workflow_event';

    /**
     * Get display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Manual => 'Manual',
            self::WorkflowEvent => 'Workflow Event',
        };
    }
}
