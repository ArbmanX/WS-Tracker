<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Global theme listener component.
 *
 * This component should be included in the main layout to handle
 * theme change events from anywhere in the application and sync
 * them to the database.
 *
 * Listens for both old and new event names during migration.
 */
class ThemeListener extends Component
{
    /**
     * Handle theme changes from any component.
     * Listens to the new Alpine store event.
     */
    #[On('theme-preference-changed')]
    public function handleThemePreferenceChanged(string $theme): void
    {
        $this->saveThemePreference($theme);
    }

    /**
     * Handle theme changes from legacy components.
     * Can be removed once migration to new layout is complete.
     */
    #[On('theme-changed')]
    public function handleThemeChanged(string $theme): void
    {
        $this->saveThemePreference($theme);
    }

    /**
     * Save theme preference to database.
     */
    protected function saveThemePreference(string $theme): void
    {
        if (Auth::check()) {
            Auth::user()->update([
                'theme_preference' => $theme,
            ]);
        }
    }

    public function render(): string
    {
        // Use inline template since this component is deprecated
        // (replaced by PreferenceSync for general preference handling)
        return <<<'HTML'
        <div class="hidden" aria-hidden="true"></div>
        HTML;
    }
}
