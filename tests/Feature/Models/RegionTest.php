<?php

use App\Models\Circuit;
use App\Models\Region;
use App\Models\User;

describe('Region Model', function () {
    test('creates region with factory', function () {
        $region = Region::factory()->create([
            'name' => 'Central',
            'code' => 'CTL',
        ]);

        expect($region)->toBeInstanceOf(Region::class)
            ->and($region->name)->toBe('Central')
            ->and($region->code)->toBe('CTL');
    });

    test('casts is_active to boolean', function () {
        $region = Region::factory()->create(['is_active' => 1]);

        expect($region->is_active)->toBeBool()->toBeTrue();
    });

    test('has circuits relationship', function () {
        $region = Region::factory()->create();
        Circuit::factory()->count(3)->create(['region_id' => $region->id]);

        expect($region->circuits)->toHaveCount(3);
    });

    test('has users relationship', function () {
        $region = Region::factory()->create();
        $users = User::factory()->count(2)->create();

        $region->users()->attach($users);

        expect($region->users)->toHaveCount(2);
    });
});

describe('Region Scopes', function () {
    test('active scope filters by is_active', function () {
        Region::factory()->create(['is_active' => true]);
        Region::factory()->create(['is_active' => true]);
        Region::factory()->create(['is_active' => false]);

        expect(Region::active()->count())->toBe(2);
    });

    test('ordered scope sorts by sort_order', function () {
        Region::factory()->create(['sort_order' => 3, 'name' => 'Third']);
        Region::factory()->create(['sort_order' => 1, 'name' => 'First']);
        Region::factory()->create(['sort_order' => 2, 'name' => 'Second']);

        $regions = Region::ordered()->get();

        expect($regions->first()->name)->toBe('First')
            ->and($regions->last()->name)->toBe('Third');
    });

    test('scopes can be chained', function () {
        Region::factory()->create(['is_active' => true, 'sort_order' => 2]);
        Region::factory()->create(['is_active' => true, 'sort_order' => 1]);
        Region::factory()->create(['is_active' => false, 'sort_order' => 0]);

        $regions = Region::active()->ordered()->get();

        expect($regions)->toHaveCount(2)
            ->and($regions->first()->sort_order)->toBe(1);
    });
});
