<?php

namespace App\Enums;

enum SnapshotTrigger: string
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';
    case Milestone50 = 'milestone_50';
    case Milestone100 = 'milestone_100';
    case StatusToQc = 'status_to_qc';

    /**
     * Get display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Scheduled => 'Scheduled Sync',
            self::Milestone50 => '50% Milestone',
            self::Milestone100 => '100% Milestone',
            self::StatusToQc => 'Status Change to QC',
        };
    }

    /**
     * Get description for UI.
     */
    public function description(): string
    {
        return match ($this) {
            self::Manual => 'Snapshot manually triggered by administrator',
            self::Scheduled => 'Automatic snapshot during scheduled sync',
            self::Milestone50 => 'Automatic snapshot when circuit reached 50% complete',
            self::Milestone100 => 'Automatic snapshot when circuit reached 100% complete',
            self::StatusToQc => 'Automatic snapshot when circuit status changed to QC',
        };
    }
}
