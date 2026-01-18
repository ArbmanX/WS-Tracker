<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Appearance Settings'])]
class Appearance extends Component
{
    public string $theme = 'system';

    /**
     * Available themes organized by category.
     *
     * @var array<string, array<string, string>>
     */
    public array $availableThemes = [
        'default' => [
            'light' => 'Light',
            'dark' => 'Dark',
            'system' => 'System',
        ],
        'ppl' => [
            'ppl-light' => 'PPL Light',
            'ppl-dark' => 'PPL Dark',
        ],
        'other' => [
            'corporate' => 'Corporate',
            'forest' => 'Forest',
            'dracula' => 'Dracula',
            'night' => 'Night',
            'winter' => 'Winter',
            'cupcake' => 'Cupcake',
        ],
    ];

    public function mount(): void
    {
        $this->theme = Auth::user()->theme_preference ?? 'system';
    }

    /**
     * Update theme preference when changed from the view.
     */
    public function updatedTheme(string $value): void
    {
        $this->saveTheme($value);
    }

    /**
     * Handle theme changes from the color-changer dropdown component.
     */
    #[On('theme-changed')]
    public function handleThemeChanged(string $theme): void
    {
        $this->theme = $theme;
        $this->saveTheme($theme);
    }

    /**
     * Save theme preference to database.
     */
    protected function saveTheme(string $theme): void
    {
        Auth::user()->update([
            'theme_preference' => $theme,
        ]);

        // Dispatch browser event for JavaScript to update the theme
        $this->dispatch('theme-updated', theme: $theme);
    }

    /**
     * Get the effective theme (resolving 'system' to actual theme).
     */
    public function getEffectiveTheme(): string
    {
        return $this->theme === 'system' ? 'light' : $this->theme;
    }

    public function render()
    {
        return view('livewire.settings.appearance');
    }
}
