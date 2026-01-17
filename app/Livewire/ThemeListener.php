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
 */
class ThemeListener extends Component
{
    /**
     * Handle theme changes from any component.
     */
    #[On('theme-changed')]
    public function handleThemeChanged(string $theme): void
    {
        if (Auth::check()) {
            Auth::user()->update([
                'theme_preference' => $theme,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.theme-listener');
    }
}
