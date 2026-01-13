<?php

use App\Models\Circuit;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Circuit Exclusion', function () {
    it('circuits are not excluded by default', function () {
        $circuit = Circuit::factory()->create();

        expect($circuit->is_excluded)->toBeFalse();
        expect($circuit->exclusion_reason)->toBeNull();
        expect($circuit->excluded_by)->toBeNull();
        expect($circuit->excluded_at)->toBeNull();
    });

    it('can create excluded circuit with factory', function () {
        $circuit = Circuit::factory()->excluded('Test reason')->create();

        expect($circuit->is_excluded)->toBeTrue();
        expect($circuit->exclusion_reason)->toBe('Test reason');
        expect($circuit->excluded_at)->not->toBeNull();
    });

    it('can exclude a circuit', function () {
        $circuit = Circuit::factory()->create();
        $user = User::factory()->create();

        $circuit->exclude('Not relevant to current scope', $user->id);

        $circuit->refresh();

        expect($circuit->is_excluded)->toBeTrue();
        expect($circuit->exclusion_reason)->toBe('Not relevant to current scope');
        expect($circuit->excluded_by)->toBe($user->id);
        expect($circuit->excluded_at)->not->toBeNull();
    });

    it('can include a previously excluded circuit', function () {
        $circuit = Circuit::factory()->excluded()->create();

        expect($circuit->is_excluded)->toBeTrue();

        $circuit->include();
        $circuit->refresh();

        expect($circuit->is_excluded)->toBeFalse();
        expect($circuit->exclusion_reason)->toBeNull();
        expect($circuit->excluded_by)->toBeNull();
        expect($circuit->excluded_at)->toBeNull();
    });

    it('has excluded_by relationship', function () {
        $user = User::factory()->create();
        $circuit = Circuit::factory()->create([
            'is_excluded' => true,
            'excluded_by' => $user->id,
            'excluded_at' => now(),
        ]);

        expect($circuit->excludedBy)->toBeInstanceOf(User::class);
        expect($circuit->excludedBy->id)->toBe($user->id);
    });

    it('calculates miles remaining', function () {
        $circuit = Circuit::factory()->create([
            'total_miles' => 25.5,
            'miles_planned' => 15.25,
        ]);

        expect($circuit->miles_remaining)->toBe(10.25);
    });

    it('can scope to excluded circuits', function () {
        Circuit::factory()->create(['is_excluded' => false]);
        Circuit::factory()->create(['is_excluded' => false]);
        $excludedCircuit = Circuit::factory()->excluded()->create();

        $excluded = Circuit::excluded()->get();

        expect($excluded)->toHaveCount(1);
        expect($excluded->first()->id)->toBe($excludedCircuit->id);
    });

    it('can scope to non-excluded circuits', function () {
        $included1 = Circuit::factory()->create(['is_excluded' => false]);
        $included2 = Circuit::factory()->create(['is_excluded' => false]);
        Circuit::factory()->excluded()->create();

        $notExcluded = Circuit::notExcluded()->get();

        expect($notExcluded)->toHaveCount(2);
        expect($notExcluded->pluck('id')->toArray())
            ->toContain($included1->id)
            ->toContain($included2->id);
    });

    it('can scope for reporting which excludes excluded circuits', function () {
        Circuit::factory()->create(['is_excluded' => false]);
        Circuit::factory()->create(['is_excluded' => false]);
        Circuit::factory()->excluded()->create();

        $forReporting = Circuit::forReporting()->get();

        expect($forReporting)->toHaveCount(2);
    });

    it('excludes circuits from region miles calculation', function () {
        $region = Region::factory()->create();

        // Create 3 circuits: 2 included, 1 excluded
        Circuit::factory()->forRegion($region)->create([
            'total_miles' => 10,
            'miles_planned' => 5,
            'is_excluded' => false,
        ]);
        Circuit::factory()->forRegion($region)->create([
            'total_miles' => 20,
            'miles_planned' => 10,
            'is_excluded' => false,
        ]);
        Circuit::factory()->forRegion($region)->create([
            'total_miles' => 100,  // This should be excluded
            'miles_planned' => 50,
            'is_excluded' => true,
        ]);

        // Get totals excluding excluded circuits
        $totals = Circuit::forReporting()
            ->where('region_id', $region->id)
            ->get();

        expect($totals)->toHaveCount(2);
        expect((float) $totals->sum('total_miles'))->toBe(30.0);
        expect((float) $totals->sum('miles_planned'))->toBe(15.0);
    });
});

describe('CircuitAggregate Miles Tracking', function () {
    it('stores miles in circuit aggregate', function () {
        $aggregate = \App\Models\CircuitAggregate::factory()->create([
            'total_miles' => 50.25,
            'miles_planned' => 30.50,
            'miles_remaining' => 19.75,
        ]);

        expect((float) $aggregate->total_miles)->toBe(50.25);
        expect((float) $aggregate->miles_planned)->toBe(30.50);
        expect((float) $aggregate->miles_remaining)->toBe(19.75);
    });
});

describe('PlannerDailyAggregate Miles Tracking', function () {
    it('stores miles planned in planner daily aggregate', function () {
        $aggregate = \App\Models\PlannerDailyAggregate::factory()->create([
            'miles_planned' => 8.5,
        ]);

        expect((float) $aggregate->miles_planned)->toBe(8.50);
    });
});
