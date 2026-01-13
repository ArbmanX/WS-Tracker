<?php

use App\Models\PlannerWeeklyAggregate;
use App\Models\Region;
use App\Models\RegionalWeeklyAggregate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('PlannerWeeklyAggregate', function () {
    it('creates a planner weekly aggregate', function () {
        $aggregate = PlannerWeeklyAggregate::factory()->create();

        expect($aggregate)->toBeInstanceOf(PlannerWeeklyAggregate::class);
        expect($aggregate->user_id)->toBeInt();
        expect($aggregate->week_ending)->toBeInstanceOf(Carbon::class);
        expect($aggregate->week_starting)->toBeInstanceOf(Carbon::class);
    });

    it('week ending is always a Saturday', function () {
        $aggregate = PlannerWeeklyAggregate::factory()->create();

        expect($aggregate->week_ending->isSaturday())->toBeTrue();
    });

    it('week starting is always a Sunday', function () {
        $aggregate = PlannerWeeklyAggregate::factory()->create();

        expect($aggregate->week_starting->isSunday())->toBeTrue();
    });

    it('calculates correct week ending for a given date', function () {
        // Test for a Wednesday
        $wednesday = Carbon::parse('2026-01-14'); // A Wednesday
        $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate($wednesday);

        expect($weekEnding->isSaturday())->toBeTrue();
        expect($weekEnding->format('Y-m-d'))->toBe('2026-01-17');
    });

    it('returns same date when given a Saturday', function () {
        $saturday = Carbon::parse('2026-01-17'); // A Saturday
        $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate($saturday);

        expect($weekEnding->format('Y-m-d'))->toBe('2026-01-17');
    });

    it('calculates correct week starting for a given date', function () {
        // Test for a Wednesday
        $wednesday = Carbon::parse('2026-01-14'); // A Wednesday
        $weekStarting = PlannerWeeklyAggregate::getWeekStartingForDate($wednesday);

        expect($weekStarting->isSunday())->toBeTrue();
        expect($weekStarting->format('Y-m-d'))->toBe('2026-01-11');
    });

    it('has user relationship', function () {
        $user = User::factory()->create();
        $aggregate = PlannerWeeklyAggregate::factory()->forUser($user)->create();

        expect($aggregate->user->id)->toBe($user->id);
    });

    it('has region relationship', function () {
        $region = Region::factory()->create();
        $aggregate = PlannerWeeklyAggregate::factory()->forRegion($region)->create();

        expect($aggregate->region->id)->toBe($region->id);
    });

    it('calculates average daily units', function () {
        $aggregate = PlannerWeeklyAggregate::factory()->create([
            'days_worked' => 5,
            'total_units_assessed' => 100,
        ]);

        expect($aggregate->getAvgDailyUnits())->toBe(20.0);
    });

    it('returns zero avg daily units when no days worked', function () {
        $aggregate = PlannerWeeklyAggregate::factory()->create([
            'days_worked' => 0,
            'total_units_assessed' => 100,
        ]);

        expect($aggregate->getAvgDailyUnits())->toBe(0.0);
    });

    it('can scope by planner', function () {
        $user = User::factory()->create();
        PlannerWeeklyAggregate::factory()->forUser($user)->create();
        PlannerWeeklyAggregate::factory()->create(); // Different user

        $results = PlannerWeeklyAggregate::forPlanner($user->id)->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->user_id)->toBe($user->id);
    });

    it('can scope by week ending', function () {
        $weekEnding = '2026-01-17';
        PlannerWeeklyAggregate::factory()->forWeekEnding($weekEnding)->create();
        PlannerWeeklyAggregate::factory()->forWeekEnding('2026-01-10')->create();

        $results = PlannerWeeklyAggregate::forWeekEnding($weekEnding)->get();

        expect($results)->toHaveCount(1);
    });
});

describe('RegionalWeeklyAggregate', function () {
    it('creates a regional weekly aggregate', function () {
        $aggregate = RegionalWeeklyAggregate::factory()->create();

        expect($aggregate)->toBeInstanceOf(RegionalWeeklyAggregate::class);
        expect($aggregate->region_id)->toBeInt();
        expect($aggregate->week_ending)->toBeInstanceOf(Carbon::class);
    });

    it('has region relationship', function () {
        $region = Region::factory()->create();
        $aggregate = RegionalWeeklyAggregate::factory()->forRegion($region)->create();

        expect($aggregate->region->id)->toBe($region->id);
    });

    it('tracks miles correctly', function () {
        $aggregate = RegionalWeeklyAggregate::factory()->create([
            'total_miles' => 1000,
            'miles_planned' => 600,
            'miles_remaining' => 400,
        ]);

        expect((float) $aggregate->total_miles)->toBe(1000.0);
        expect((float) $aggregate->miles_planned)->toBe(600.0);
        expect((float) $aggregate->miles_remaining)->toBe(400.0);
    });

    it('calculates completion percentage', function () {
        $aggregate = RegionalWeeklyAggregate::factory()->create([
            'total_miles' => 1000,
            'miles_planned' => 750,
        ]);

        expect($aggregate->getCompletionPercentage())->toBe(75.0);
    });

    it('returns zero completion when no miles', function () {
        $aggregate = RegionalWeeklyAggregate::factory()->create([
            'total_miles' => 0,
            'miles_planned' => 0,
        ]);

        expect($aggregate->getCompletionPercentage())->toBe(0.0);
    });

    it('tracks excluded circuits count', function () {
        $aggregate = RegionalWeeklyAggregate::factory()->create([
            'total_circuits' => 100,
            'excluded_circuits' => 10,
        ]);

        expect($aggregate->total_circuits)->toBe(100);
        expect($aggregate->excluded_circuits)->toBe(10);
    });

    it('can scope by region', function () {
        $region = Region::factory()->create();
        RegionalWeeklyAggregate::factory()->forRegion($region)->create();
        RegionalWeeklyAggregate::factory()->create(); // Different region

        $results = RegionalWeeklyAggregate::forRegion($region->id)->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->region_id)->toBe($region->id);
    });
});
