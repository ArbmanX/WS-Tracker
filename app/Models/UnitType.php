<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UnitType extends Model
{
    /**
     * Unit categories from WorkStudio.
     */
    public const CATEGORY_LINE = 'VLG';       // Vegetation Line/Length (linear feet)

    public const CATEGORY_AREA = 'VAR';       // Variable Area (acres)

    public const CATEGORY_TREE = 'VCT';       // Vegetation Count/Tree (tree count)

    public const CATEGORY_NO_WORK = 'VNW';    // Vegetation No Work

    public const CATEGORY_SENSITIVE = 'VSA';  // Vegetation Sensitive Area

    /**
     * Measurement types mapped to API fields.
     */
    public const MEASUREMENT_LINEAR_FT = 'linear_ft';

    public const MEASUREMENT_ACRES = 'acres';

    public const MEASUREMENT_TREE_COUNT = 'tree_count';

    public const MEASUREMENT_NONE = 'none';

    /**
     * API field mappings for aggregation.
     */
    public const API_FIELD_MAP = [
        self::MEASUREMENT_LINEAR_FT => 'JOBVEGETATIONUNITS_LENGTHWRK',
        self::MEASUREMENT_ACRES => 'JOBVEGETATIONUNITS_ACRES',
        self::MEASUREMENT_TREE_COUNT => 'JOBVEGETATIONUNITS_NUMTREES',
    ];

    protected $fillable = [
        'code',
        'name',
        'category',
        'measurement_type',
        'dbh_min',
        'dbh_max',
        'species',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'dbh_min' => 'decimal:1',
            'dbh_max' => 'decimal:1',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Filter to only active unit types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter by category (VLG, VAR, VCT, VNW, VSA).
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Filter by measurement type.
     */
    public function scopeMeasurement(Builder $query, string $measurementType): Builder
    {
        return $query->where('measurement_type', $measurementType);
    }

    /**
     * Get all line trimming units (linear feet).
     */
    public function scopeLineTrimming(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_LINE);
    }

    /**
     * Get all brush/herbicide units (acres).
     */
    public function scopeBrushArea(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_AREA);
    }

    /**
     * Get all tree removal units (tree count).
     */
    public function scopeTreeRemoval(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_TREE);
    }

    /**
     * Get only tree removal units (excludes ash-specific).
     */
    public function scopeStandardTreeRemoval(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_TREE)
            ->whereNull('species');
    }

    /**
     * Get ash tree removal units (EAB program).
     */
    public function scopeAshRemoval(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_TREE)
            ->where('species', 'ash');
    }

    /**
     * Get units that represent actual work (exclude no-work flags).
     */
    public function scopeWorkUnits(Builder $query): Builder
    {
        return $query->whereNotIn('category', [self::CATEGORY_NO_WORK, self::CATEGORY_SENSITIVE]);
    }

    /**
     * Order by sort_order then code.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the DBH range as a formatted string.
     */
    public function getDbhRangeAttribute(): ?string
    {
        if ($this->dbh_min === null) {
            return null;
        }

        if ($this->dbh_max === null) {
            return "> {$this->dbh_min}\"";
        }

        return "{$this->dbh_min}\" - {$this->dbh_max}\"";
    }

    /**
     * Get human-readable category name.
     */
    public function getCategoryNameAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_LINE => 'Line Trimming',
            self::CATEGORY_AREA => 'Brush/Herbicide',
            self::CATEGORY_TREE => 'Tree Removal',
            self::CATEGORY_NO_WORK => 'No Work',
            self::CATEGORY_SENSITIVE => 'Sensitive Area',
            default => $this->category,
        };
    }

    /**
     * Get human-readable measurement unit.
     */
    public function getMeasurementUnitAttribute(): string
    {
        return match ($this->measurement_type) {
            self::MEASUREMENT_LINEAR_FT => 'ft',
            self::MEASUREMENT_ACRES => 'acres',
            self::MEASUREMENT_TREE_COUNT => 'trees',
            default => '',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this unit type uses linear feet measurement.
     */
    public function usesLinearFeet(): bool
    {
        return $this->measurement_type === self::MEASUREMENT_LINEAR_FT;
    }

    /**
     * Check if this unit type uses acres measurement.
     */
    public function usesAcres(): bool
    {
        return $this->measurement_type === self::MEASUREMENT_ACRES;
    }

    /**
     * Check if this unit type uses tree count measurement.
     */
    public function usesTreeCount(): bool
    {
        return $this->measurement_type === self::MEASUREMENT_TREE_COUNT;
    }

    /**
     * Check if this unit represents actual work.
     */
    public function isWorkUnit(): bool
    {
        return ! in_array($this->category, [self::CATEGORY_NO_WORK, self::CATEGORY_SENSITIVE]);
    }

    /**
     * Get the API field name for this unit's measurement.
     */
    public function getApiFieldName(): ?string
    {
        return self::API_FIELD_MAP[$this->measurement_type] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods (Cached Lookups)
    |--------------------------------------------------------------------------
    */

    /**
     * Get all unit types as a cached collection keyed by code.
     */
    public static function allByCode(): Collection
    {
        return Cache::remember('unit_types.by_code', 3600, function () {
            return static::active()->ordered()->get()->keyBy('code');
        });
    }

    /**
     * Get unit types grouped by category.
     */
    public static function byCategory(): Collection
    {
        return Cache::remember('unit_types.by_category', 3600, function () {
            return static::active()->ordered()->get()->groupBy('category');
        });
    }

    /**
     * Get unit types grouped by measurement type.
     */
    public static function byMeasurement(): Collection
    {
        return Cache::remember('unit_types.by_measurement', 3600, function () {
            return static::active()->ordered()->get()->groupBy('measurement_type');
        });
    }

    /**
     * Find a unit type by code (cached).
     */
    public static function findByCode(string $code): ?self
    {
        return static::allByCode()->get($code);
    }

    /**
     * Get all codes for a specific category.
     */
    public static function codesForCategory(string $category): array
    {
        return static::byCategory()->get($category, collect())->pluck('code')->toArray();
    }

    /**
     * Get all codes for a specific measurement type.
     */
    public static function codesForMeasurement(string $measurementType): array
    {
        return static::byMeasurement()->get($measurementType, collect())->pluck('code')->toArray();
    }

    /**
     * Get measurement type for a given unit code.
     */
    public static function measurementTypeFor(string $code): ?string
    {
        return static::findByCode($code)?->measurement_type;
    }

    /**
     * Clear the unit type cache (call after seeding or updates).
     */
    public static function clearCache(): void
    {
        Cache::forget('unit_types.by_code');
        Cache::forget('unit_types.by_category');
        Cache::forget('unit_types.by_measurement');
    }

    /**
     * Get aggregation groups for dashboard display.
     */
    public static function aggregationGroups(): array
    {
        return [
            'trim_line' => [
                'label' => 'Line Trimming',
                'measurement' => self::MEASUREMENT_LINEAR_FT,
                'unit_label' => 'Linear Feet',
                'codes' => static::codesForCategory(self::CATEGORY_LINE),
            ],
            'brush_area' => [
                'label' => 'Brush/Herbicide',
                'measurement' => self::MEASUREMENT_ACRES,
                'unit_label' => 'Acres',
                'codes' => static::codesForCategory(self::CATEGORY_AREA),
            ],
            'tree_removal' => [
                'label' => 'Tree Removal',
                'measurement' => self::MEASUREMENT_TREE_COUNT,
                'unit_label' => 'Trees',
                'codes' => static::codesForCategory(self::CATEGORY_TREE),
            ],
        ];
    }
}
