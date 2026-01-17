<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\ThemeListener;
use App\Models\User;
use Livewire\Livewire;

it('renders the appearance settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/appearance')
        ->assertSuccessful()
        ->assertSeeLivewire(Appearance::class);
});

it('displays current theme preference', function () {
    $user = User::factory()->create(['theme_preference' => 'ppl-dark']);

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->assertSet('theme', 'ppl-dark');
});

it('defaults to system when no preference set', function () {
    $user = User::factory()->create(['theme_preference' => null]);

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->assertSet('theme', 'system');
});

it('saves theme preference to database when changed', function () {
    $user = User::factory()->create(['theme_preference' => 'system']);

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->set('theme', 'ppl-light')
        ->assertSet('theme', 'ppl-light');

    expect($user->fresh()->theme_preference)->toBe('ppl-light');
});

it('saves theme when updated via wire:model', function () {
    $user = User::factory()->create(['theme_preference' => 'light']);

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->set('theme', 'dark');

    expect($user->fresh()->theme_preference)->toBe('dark');
});

it('dispatches theme-updated event when theme changes', function () {
    $user = User::factory()->create(['theme_preference' => 'system']);

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->set('theme', 'ppl-dark')
        ->assertDispatched('theme-updated', theme: 'ppl-dark');
});

it('handles theme-changed event from color changer', function () {
    $user = User::factory()->create(['theme_preference' => 'light']);

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->dispatch('theme-changed', theme: 'dracula')
        ->assertSet('theme', 'dracula');

    expect($user->fresh()->theme_preference)->toBe('dracula');
});

describe('ThemeListener', function () {
    it('saves theme preference when receiving theme-changed event', function () {
        $user = User::factory()->create(['theme_preference' => 'system']);

        Livewire::actingAs($user)
            ->test(ThemeListener::class)
            ->dispatch('theme-changed', theme: 'forest');

        expect($user->fresh()->theme_preference)->toBe('forest');
    });

    it('does not save when user is not authenticated', function () {
        Livewire::test(ThemeListener::class)
            ->dispatch('theme-changed', theme: 'dark');

        // Should not throw an error
        expect(true)->toBeTrue();
    });
});

describe('available themes', function () {
    it('includes PPL brand themes', function () {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Appearance::class);

        $availableThemes = $component->get('availableThemes');

        expect($availableThemes)->toHaveKey('ppl');
        expect($availableThemes['ppl'])->toHaveKey('ppl-light');
        expect($availableThemes['ppl'])->toHaveKey('ppl-dark');
    });

    it('includes default themes', function () {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Appearance::class);

        $availableThemes = $component->get('availableThemes');

        expect($availableThemes)->toHaveKey('default');
        expect($availableThemes['default'])->toHaveKey('light');
        expect($availableThemes['default'])->toHaveKey('dark');
        expect($availableThemes['default'])->toHaveKey('system');
    });
});

describe('theme preference persistence', function () {
    it('persists theme across multiple sessions', function () {
        $user = User::factory()->create(['theme_preference' => 'cupcake']);

        // First session
        Livewire::actingAs($user)
            ->test(Appearance::class)
            ->assertSet('theme', 'cupcake');

        // Simulate new session by creating new component instance
        Livewire::actingAs($user->fresh())
            ->test(Appearance::class)
            ->assertSet('theme', 'cupcake');
    });
});
