<?php

namespace App\Services\WorkStudio\Sync;

use App\Enums\AssignmentSource;
use App\Models\Circuit;
use App\Models\UnlinkedPlanner;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for syncing circuits from the WorkStudio API.
 *
 * Implements user-priority sync logic:
 * - Fields modified by users are preserved during normal sync
 * - Force-overwrite mode updates all fields and clears modification tracking
 * - API-only fields (identifiers, status) are always synced
 */
class CircuitSyncService
{
    /**
     * Results from the last sync operation.
     */
    private array $syncResults = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'unchanged' => 0,
        'errors' => [],
        'user_preserved_fields' => [],
    ];

    /**
     * Sync a collection of circuits from API data.
     *
     * @param  Collection<array>  $circuitDataCollection  Transformed circuit data from API
     * @param  bool  $forceOverwrite  If true, overwrite user modifications
     * @return array Sync results
     */
    public function syncCircuits(Collection $circuitDataCollection, bool $forceOverwrite = false): array
    {
        $this->resetResults();

        foreach ($circuitDataCollection as $circuitData) {
            try {
                $this->syncCircuit($circuitData, $forceOverwrite);
            } catch (\Exception $e) {
                $this->syncResults['errors'][] = [
                    'job_guid' => $circuitData['job_guid'] ?? 'unknown',
                    'work_order' => $circuitData['work_order'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->syncResults;
    }

    /**
     * Sync a single circuit from API data.
     *
     * @param  array  $apiData  Transformed circuit data from API
     * @param  bool  $forceOverwrite  If true, overwrite user modifications
     * @return Circuit The synced circuit
     */
    public function syncCircuit(array $apiData, bool $forceOverwrite = false): Circuit
    {
        $jobGuid = $apiData['job_guid'] ?? null;

        if (empty($jobGuid)) {
            throw new \InvalidArgumentException('Circuit data must contain job_guid');
        }

        $existingCircuit = Circuit::where('job_guid', $jobGuid)->first();

        if ($existingCircuit) {
            return $this->updateCircuit($existingCircuit, $apiData, $forceOverwrite);
        }

        return $this->createCircuit($apiData);
    }

    /**
     * Create a new circuit from API data.
     */
    protected function createCircuit(array $apiData): Circuit
    {
        return DB::transaction(function () use ($apiData) {
            // For new circuits, we use all the API data
            $circuitData = $this->prepareDataForCreate($apiData);
            $circuitData['last_synced_at'] = now();

            $circuit = Circuit::create($circuitData);

            // Create UI state for new circuits
            $circuit->getOrCreateUiState();

            $this->syncResults['created']++;

            return $circuit;
        });
    }

    /**
     * Update an existing circuit with API data, respecting user modifications.
     */
    protected function updateCircuit(Circuit $circuit, array $apiData, bool $forceOverwrite): Circuit
    {
        return DB::transaction(function () use ($circuit, $apiData, $forceOverwrite) {
            $dataToUpdate = $this->prepareDataForUpdate($circuit, $apiData, $forceOverwrite);

            if (empty($dataToUpdate)) {
                $this->syncResults['unchanged']++;

                return $circuit;
            }

            // Add sync timestamp
            $dataToUpdate['last_synced_at'] = now();

            // If force overwrite, clear user modification tracking
            if ($forceOverwrite && $circuit->hasUserModifications()) {
                $dataToUpdate['user_modified_fields'] = null;
                $dataToUpdate['last_user_modified_at'] = null;
                $dataToUpdate['last_user_modified_by'] = null;
            }

            $circuit->update($dataToUpdate);

            $this->syncResults['updated']++;

            return $circuit;
        });
    }

    /**
     * Prepare data for creating a new circuit.
     * Filters to only include valid circuit columns.
     */
    protected function prepareDataForCreate(array $apiData): array
    {
        $allowedFields = array_merge(
            Circuit::API_ONLY_FIELDS,
            Circuit::SYNCABLE_FIELDS,
            ['parent_circuit_id'] // Allow parent linking on create
        );

        return array_intersect_key($apiData, array_flip($allowedFields));
    }

    /**
     * Prepare data for updating an existing circuit.
     * Respects user modifications unless force overwrite is enabled.
     *
     * @return array Fields to update (empty if no changes needed)
     */
    protected function prepareDataForUpdate(Circuit $circuit, array $apiData, bool $forceOverwrite): array
    {
        $dataToUpdate = [];
        $preservedFields = [];

        // Always update API-only fields (these are never user-modifiable)
        foreach (Circuit::API_ONLY_FIELDS as $field) {
            if (array_key_exists($field, $apiData)) {
                $newValue = $apiData[$field];
                $currentValue = $circuit->$field;

                // Only include if actually changed
                if ($this->hasValueChanged($currentValue, $newValue)) {
                    $dataToUpdate[$field] = $newValue;
                }
            }
        }

        // Handle syncable fields with user-priority logic
        foreach (Circuit::SYNCABLE_FIELDS as $field) {
            if (! array_key_exists($field, $apiData)) {
                continue;
            }

            $newValue = $apiData[$field];
            $currentValue = $circuit->$field;

            // Skip if value hasn't changed
            if (! $this->hasValueChanged($currentValue, $newValue)) {
                continue;
            }

            // Check if field was user-modified
            if (! $forceOverwrite && $circuit->isFieldUserModified($field)) {
                // Preserve user modification - skip this field
                $preservedFields[$field] = [
                    'user_value' => $currentValue,
                    'api_value' => $newValue,
                ];

                continue;
            }

            // Update the field
            $dataToUpdate[$field] = $newValue;
        }

        // Track preserved fields for reporting
        if (! empty($preservedFields)) {
            $this->syncResults['user_preserved_fields'][$circuit->job_guid] = $preservedFields;
        }

        return $dataToUpdate;
    }

    /**
     * Check if a value has changed, handling type coercion and null values.
     */
    protected function hasValueChanged(mixed $current, mixed $new): bool
    {
        // Handle null cases
        if ($current === null && $new === null) {
            return false;
        }
        if ($current === null || $new === null) {
            return true;
        }

        // Handle numeric comparisons (account for decimal precision)
        if (is_numeric($current) && is_numeric($new)) {
            return abs((float) $current - (float) $new) > 0.001;
        }

        // Handle date comparisons
        if ($current instanceof \DateTimeInterface && $new instanceof \DateTimeInterface) {
            return $current->format('Y-m-d') !== $new->format('Y-m-d');
        }

        // Handle array/json comparisons
        if (is_array($current) && is_array($new)) {
            return json_encode($current) !== json_encode($new);
        }

        // Default string comparison
        return (string) $current !== (string) $new;
    }

    /**
     * Sync planner assignments for a circuit.
     *
     * @param  Circuit  $circuit  The circuit to sync planners for
     * @param  array<string>  $plannerIdentifiers  Planner identifiers from API
     * @return array{linked: int, unlinked: int}
     */
    public function syncPlanners(Circuit $circuit, array $plannerIdentifiers): array
    {
        $linked = 0;
        $unlinked = 0;

        foreach ($plannerIdentifiers as $identifier) {
            $identifier = trim($identifier);
            if (empty($identifier)) {
                continue;
            }

            // Try to find user by WorkStudio username
            $user = User::where('ws_username', $identifier)->first();

            if ($user) {
                // Link the planner to the circuit
                $circuit->planners()->syncWithoutDetaching([
                    $user->id => [
                        'assignment_source' => AssignmentSource::ApiSync->value,
                        'assigned_at' => now(),
                        'ws_user_guid' => $user->ws_user_guid,
                    ],
                ]);
                $linked++;
            } else {
                // Track unlinked planner for manual linking later
                $existing = UnlinkedPlanner::where('ws_username', $identifier)->first();

                if ($existing) {
                    $existing->update([
                        'last_seen_at' => now(),
                        'circuit_count' => ($existing->circuit_count ?? 0) + 1,
                    ]);
                } else {
                    UnlinkedPlanner::create([
                        'ws_username' => $identifier,
                        'ws_user_guid' => 'pending-'.md5($identifier), // Temporary GUID until linked
                        'display_name' => $identifier,
                        'first_seen_at' => now(),
                        'last_seen_at' => now(),
                        'circuit_count' => 1,
                    ]);
                }

                $unlinked++;
            }
        }

        return ['linked' => $linked, 'unlinked' => $unlinked];
    }

    /**
     * Get a summary of what would change without actually syncing.
     * Useful for previewing a sync operation.
     *
     * @param  Collection<array>  $circuitDataCollection  Transformed circuit data
     * @param  bool  $forceOverwrite  Whether force overwrite would be used
     * @return array Preview of changes
     */
    public function previewSync(Collection $circuitDataCollection, bool $forceOverwrite = false): array
    {
        $preview = [
            'would_create' => 0,
            'would_update' => 0,
            'would_preserve' => [],
            'unchanged' => 0,
        ];

        foreach ($circuitDataCollection as $apiData) {
            $jobGuid = $apiData['job_guid'] ?? null;
            if (empty($jobGuid)) {
                continue;
            }

            $existingCircuit = Circuit::where('job_guid', $jobGuid)->first();

            if (! $existingCircuit) {
                $preview['would_create']++;

                continue;
            }

            $dataToUpdate = $this->prepareDataForUpdate($existingCircuit, $apiData, $forceOverwrite);

            if (empty($dataToUpdate)) {
                $preview['unchanged']++;
            } else {
                $preview['would_update']++;

                // Check for preserved fields
                if (! $forceOverwrite && $existingCircuit->hasUserModifications()) {
                    $preserved = [];
                    foreach ($existingCircuit->getUserModifiedFieldNames() as $field) {
                        if (array_key_exists($field, $apiData)) {
                            $preserved[$field] = [
                                'user_value' => $existingCircuit->$field,
                                'api_value' => $apiData[$field],
                            ];
                        }
                    }
                    if (! empty($preserved)) {
                        $preview['would_preserve'][$jobGuid] = $preserved;
                    }
                }
            }
        }

        // Reset results since previewSync modified them
        $this->resetResults();

        return $preview;
    }

    /**
     * Get the results from the last sync operation.
     */
    public function getResults(): array
    {
        return $this->syncResults;
    }

    /**
     * Reset sync results.
     */
    protected function resetResults(): void
    {
        $this->syncResults = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'unchanged' => 0,
            'errors' => [],
            'user_preserved_fields' => [],
        ];
    }
}
