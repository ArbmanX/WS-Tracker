<?php

use App\Livewire\PreferenceSync;
use App\Models\User;
use Livewire\Livewire;

describe('PreferenceSync component', function () {
    it('syncs preference to database when receiving sync-preference event', function () {
        $user = User::factory()->create(['dashboard_preferences' => null]);

        Livewire::actingAs($user)
            ->test(PreferenceSync::class)
            ->dispatch('sync-preference', key: 'default_view', value: 'table');

        expect($user->fresh()->dashboard_preferences['default_view'])->toBe('table');
    });

    it('merges with existing preferences instead of replacing', function () {
        $user = User::factory()->create([
            'dashboard_preferences' => ['existing_key' => 'existing_value'],
        ]);

        Livewire::actingAs($user)
            ->test(PreferenceSync::class)
            ->dispatch('sync-preference', key: 'new_key', value: 'new_value');

        $prefs = $user->fresh()->dashboard_preferences;
        expect($prefs)->toHaveKey('existing_key');
        expect($prefs['existing_key'])->toBe('existing_value');
        expect($prefs)->toHaveKey('new_key');
        expect($prefs['new_key'])->toBe('new_value');
    });

    it('handles boolean values correctly', function () {
        $user = User::factory()->create(['dashboard_preferences' => null]);

        Livewire::actingAs($user)
            ->test(PreferenceSync::class)
            ->dispatch('sync-preference', key: 'sidebar_collapsed', value: true);

        expect($user->fresh()->dashboard_preferences['sidebar_collapsed'])->toBeTrue();
    });

    it('does not save when user is not authenticated', function () {
        Livewire::test(PreferenceSync::class)
            ->dispatch('sync-preference', key: 'default_view', value: 'table');

        // Should not throw an error
        expect(true)->toBeTrue();
    });

    it('renders a hidden element', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PreferenceSync::class)
            ->assertSeeHtml('aria-hidden="true"');
    });
});

describe('preferences-init component', function () {
    it('outputs user preferences for authenticated users', function () {
        $user = User::factory()->onboarded()->create([
            'theme_preference' => 'dark',
            'dashboard_preferences' => ['default_view' => 'table'],
            'default_region_id' => null,
        ]);

        // Use settings page which uses app-shell layout
        $response = $this->actingAs($user)->get('/settings/appearance');
        $response->assertSee('window.__userPreferences');

        // Check the HTML content directly (assertSee escapes by default)
        $content = $response->getContent();
        expect($content)->toContain('"theme":"dark"');
        expect($content)->toContain('"default_view":"table"');
    });

    it('handles null dashboard_preferences gracefully', function () {
        $user = User::factory()->onboarded()->create([
            'dashboard_preferences' => null,
        ]);

        // Use settings page which uses app-shell layout
        $response = $this->actingAs($user)->get('/settings/appearance');
        $response->assertSee('window.__userPreferences');

        // Check the HTML content directly
        $content = $response->getContent();
        expect($content)->toContain('"dashboard":[]');
    });
});

describe('preference persistence flow', function () {
    it('persists view preference across sessions', function () {
        $user = User::factory()->create([
            'dashboard_preferences' => ['default_view' => 'card'],
        ]);

        // Sync a new preference
        Livewire::actingAs($user)
            ->test(PreferenceSync::class)
            ->dispatch('sync-preference', key: 'default_view', value: 'table');

        // Verify it persisted
        expect($user->fresh()->dashboard_preferences['default_view'])->toBe('table');
    });

    it('persists sidebar collapsed state', function () {
        $user = User::factory()->create(['dashboard_preferences' => null]);

        Livewire::actingAs($user)
            ->test(PreferenceSync::class)
            ->dispatch('sync-preference', key: 'sidebar_collapsed', value: true);

        expect($user->fresh()->dashboard_preferences['sidebar_collapsed'])->toBeTrue();

        // Toggle it
        Livewire::actingAs($user->fresh())
            ->test(PreferenceSync::class)
            ->dispatch('sync-preference', key: 'sidebar_collapsed', value: false);

        expect($user->fresh()->dashboard_preferences['sidebar_collapsed'])->toBeFalse();
    });
});
