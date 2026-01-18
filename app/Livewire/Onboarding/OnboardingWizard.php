<?php

namespace App\Livewire\Onboarding;

use App\Models\Region;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.onboarding')]
class OnboardingWizard extends Component
{
    public int $step = 1;

    public const TOTAL_STEPS = 5;

    // Step 1: Email verification (pre-filled, disabled)
    public string $email = '';

    public string $temporaryPassword = '';

    // Step 2: Set password & confirm name
    public string $name = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    // Step 3: Theme selection
    public string $selectedTheme = 'system';

    // Step 4: Dashboard preferences
    public string $defaultView = 'cards';

    public bool $showAllRegions = true;

    public ?int $selectedRegionId = null;

    public function mount(): void
    {
        $user = Auth::user();

        // If already onboarded, redirect to dashboard
        if ($user->isOnboarded()) {
            $this->redirect(route('dashboard'));

            return;
        }

        $this->email = $user->email;
        $this->name = $user->name;
        $this->selectedTheme = $user->theme_preference ?? 'system';
        $this->selectedRegionId = $user->default_region_id;
    }

    #[Computed]
    public function regions(): Collection
    {
        return Region::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Step 1: Verify temporary password
     */
    public function verifyCredentials(): void
    {
        $this->validate([
            'temporaryPassword' => ['required', 'string'],
        ]);

        if (! Hash::check($this->temporaryPassword, Auth::user()->password)) {
            $this->addError('temporaryPassword', 'The temporary password is incorrect.');

            return;
        }

        $this->step = 2;
    }

    /**
     * Step 2: Set new password
     */
    public function setPassword(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'newPassword' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'name' => $this->name,
            'password' => Hash::make($this->newPassword),
        ]);

        $this->step = 3;
    }

    /**
     * Step 3: Save theme preference
     */
    public function saveTheme(): void
    {
        $this->validate([
            'selectedTheme' => ['required', 'string'],
        ]);

        Auth::user()->update([
            'theme_preference' => $this->selectedTheme,
        ]);

        // Update localStorage via dispatch
        $this->dispatch('theme-updated', theme: $this->selectedTheme);

        $this->step = 4;
    }

    /**
     * Step 4: Save dashboard preferences
     */
    public function savePreferences(): void
    {
        $rules = [
            'defaultView' => ['required', 'in:cards,table'],
            'showAllRegions' => ['boolean'],
        ];

        if (! $this->showAllRegions) {
            $rules['selectedRegionId'] = ['required', 'exists:regions,id'];
        }

        $this->validate($rules);

        Auth::user()->update([
            'dashboard_preferences' => [
                'default_view' => $this->defaultView,
                'show_all_regions' => $this->showAllRegions,
                'default_region_id' => $this->showAllRegions ? null : $this->selectedRegionId,
            ],
            'default_region_id' => $this->showAllRegions ? null : $this->selectedRegionId,
        ]);

        $this->step = 5;
    }

    /**
     * Step 5: Complete onboarding
     */
    public function complete(): void
    {
        Auth::user()->markAsOnboarded();

        $this->redirect(route('dashboard'));
    }

    /**
     * Go back to previous step
     */
    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function render()
    {
        return view('livewire.onboarding.wizard')
            ->layout('components.layout.onboarding', [
                'step' => $this->step,
                'totalSteps' => self::TOTAL_STEPS,
                'title' => 'Welcome',
            ]);
    }
}
