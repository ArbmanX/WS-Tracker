<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * PreferenceSync Component
 *
 * A global Livewire component that listens for preference changes from Alpine.js
 * stores and persists them to the user's dashboard_preferences JSON column.
 *
 * This enables cross-device preference sync while Alpine stores handle
 * immediate state and localStorage serves as a fast cache.
 *
 * Include in layouts with: @auth <livewire:preference-sync /> @endauth
 */
class PreferenceSync extends Component
{
    /**
     * Handle preference sync from Alpine stores.
     *
     * Called via: Livewire.dispatch('sync-preference', { key, value })
     */
    #[On('sync-preference')]
    public function syncPreference(string $key, mixed $value): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();
        $prefs = $user->dashboard_preferences ?? [];
        $prefs[$key] = $value;

        $user->update(['dashboard_preferences' => $prefs]);
    }

    public function render(): string
    {
        return <<<'HTML'
        <div class="hidden" aria-hidden="true"></div>
        HTML;
    }
}
