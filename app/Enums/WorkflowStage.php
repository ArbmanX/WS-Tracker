<?php

namespace App\Enums;

enum WorkflowStage: string
{
    case Active = 'active';
    case PendingPermissions = 'pending_permissions';
    case Qc = 'qc';
    case Rework = 'rework';
    case Closed = 'closed';

    /**
     * Get display label for the stage.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PendingPermissions => 'Pending Permissions',
            self::Qc => 'QC',
            self::Rework => 'Rework',
            self::Closed => 'Closed',
        };
    }

    /**
     * Get DaisyUI color class for the stage.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'primary',
            self::PendingPermissions => 'warning',
            self::Qc => 'info',
            self::Rework => 'error',
            self::Closed => 'neutral',
        };
    }

    /**
     * Get icon name for the stage.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Active => 'play-circle',
            self::PendingPermissions => 'clock',
            self::Qc => 'clipboard-check',
            self::Rework => 'arrow-path',
            self::Closed => 'check-circle',
        };
    }

    /**
     * Get all stages for UI dropdowns.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $stage) => ['value' => $stage->value, 'label' => $stage->label()],
            self::cases()
        );
    }
}
