<?php

namespace App\Support;

final class WorkStudioStatus
{
    public const ACTIVE = 'ACTIV';

    public const QC = 'QC';

    public const REWORK = 'REWRK';

    public const CLOSED = 'CLOSE';

    /**
     * All primary assessment statuses used in app filtering and sync workflows.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return [self::ACTIVE, self::QC, self::REWORK, self::CLOSED];
    }

    /**
     * Default filter for board-style assessment screens.
     *
     * @return array<string>
     */
    public static function defaultFilter(): array
    {
        return [self::ACTIVE];
    }

    /**
     * Statuses included in planned-units/aggregate sync by default.
     *
     * @return array<string>
     */
    public static function plannedUnitsSyncable(): array
    {
        return [self::ACTIVE, self::QC, self::REWORK];
    }

    /**
     * Human-friendly labels for UI controls.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::ACTIVE => 'Active',
            self::QC => 'Quality Control',
            self::REWORK => 'Rework',
            self::CLOSED => 'Closed',
        ];
    }

    public static function labelFor(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }

    public static function badgeClass(string $status): string
    {
        return match ($status) {
            self::ACTIVE => 'badge-primary',
            self::QC => 'badge-warning',
            self::REWORK => 'badge-error',
            self::CLOSED => 'badge-success',
            default => 'badge-neutral',
        };
    }

    public static function daisyColor(string $status): string
    {
        return match ($status) {
            self::ACTIVE => 'primary',
            self::QC => 'warning',
            self::REWORK => 'error',
            self::CLOSED => 'success',
            default => 'neutral',
        };
    }

    /**
     * Kanban column configuration.
     *
     * @return array<string, array{label: string, color: string, description: string}>
     */
    public static function kanbanColumns(): array
    {
        return [
            self::ACTIVE => [
                'label' => 'Active',
                'color' => 'primary',
                'description' => 'Circuits currently being worked',
            ],
            self::QC => [
                'label' => 'Quality Control',
                'color' => 'warning',
                'description' => 'Awaiting QC review',
            ],
            self::REWORK => [
                'label' => 'Rework',
                'color' => 'error',
                'description' => 'Needs corrections',
            ],
            self::CLOSED => [
                'label' => 'Closed',
                'color' => 'success',
                'description' => 'Completed circuits',
            ],
        ];
    }
}
