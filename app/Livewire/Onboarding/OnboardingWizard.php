<?php

namespace App\Livewire\Onboarding;

use App\Models\Region;
use App\Models\User;
use App\Models\UserWsCredential;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.onboarding')]
class OnboardingWizard extends Component
{
    public int $step = 1;

    public const TOTAL_STEPS = 6;

    // Step 1: Email verification (pre-filled, disabled)
    public string $email = '';

    public string $temporaryPassword = '';

    // Step 2: Set password & confirm name
    public string $name = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    // Step 3: WorkStudio credentials
    public string $wsUsername = '';

    public string $wsPassword = '';

    public bool $skipWsCredentials = false;

    public bool $wsCredentialsValid = false;

    public string $wsValidationError = '';

    // Step 4: Theme selection
    public string $selectedTheme = 'system';

    // Step 5: Dashboard preferences
    public string $defaultView = 'cards';

    public bool $showAllRegions = true;

    public ?int $selectedRegionId = null;

    public function mount(): void
    {
        $user = Auth::user();

        // Guest: start at step 1 (authentication)
        if (! $user) {
            $this->step = 1;

            return;
        }

        // Onboarded users shouldn't be here - redirect to dashboard
        if ($user->isOnboarded()) {
            $this->redirect(route('dashboard'));

            return;
        }

        // Authenticated but pending: skip step 1 (already logged in)
        $this->email = $user->email;
        $this->name = $user->name;
        $this->selectedTheme = $user->theme_preference ?? 'system';
        $this->selectedRegionId = $user->default_region_id;
        $this->step = 2;
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
     * Step 1: Verify email and temporary password (authenticates guest users)
     */
    public function verifyCredentials(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'temporaryPassword' => ['required', 'string'],
        ]);

        // Find user by email
        $user = User::where('email', $this->email)->first();

        if (! $user) {
            $this->addError('email', 'No account found with this email address.');

            return;
        }

        // Check if already onboarded (should use login page instead)
        if ($user->isOnboarded()) {
            $this->addError('email', 'This account has already been set up. Please use the login page.');

            return;
        }

        // Verify password
        if (! Hash::check($this->temporaryPassword, $user->password)) {
            $this->addError('temporaryPassword', 'The temporary password is incorrect.');

            return;
        }

        // Authenticate and log in the user
        Auth::login($user);

        // Populate form fields for next steps
        $this->name = $user->name;
        $this->selectedTheme = $user->theme_preference ?? 'system';
        $this->selectedRegionId = $user->default_region_id;

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
     * Step 3: Save WorkStudio credentials
     */
    public function saveWsCredentials(): void
    {
        $this->wsValidationError = '';

        // If skipping, just move to next step
        if ($this->skipWsCredentials) {
            $this->step = 4;

            return;
        }

        $this->validate([
            'wsUsername' => ['required', 'string', 'max:255'],
            'wsPassword' => ['required', 'string'],
        ]);

        // Validate credentials against WorkStudio API
        $isValid = $this->validateWsCredentials();

        if (! $isValid) {
            return;
        }

        // Store encrypted credentials
        $user = Auth::user();

        // Delete existing credentials if any
        $user->wsCredentials?->delete();

        // Create new credentials
        UserWsCredential::create([
            'user_id' => $user->id,
            'encrypted_username' => $this->wsUsername,
            'encrypted_password' => $this->wsPassword,
            'is_valid' => true,
            'validated_at' => now(),
        ]);

        // Update user's WS link status
        $user->update([
            'ws_username' => $this->wsUsername,
            'is_ws_linked' => true,
            'ws_linked_at' => now(),
        ]);

        $this->wsCredentialsValid = true;
        $this->step = 4;
    }

    /**
     * Validate WorkStudio credentials against the API.
     */
    protected function validateWsCredentials(): bool
    {
        try {
            $baseUrl = config('workstudio.base_url');
            $timeout = config('workstudio.connect_timeout', 10);

            // Make a simple API call to validate credentials
            $response = Http::timeout($timeout)
                ->connectTimeout($timeout)
                ->withBasicAuth($this->wsUsername, $this->wsPassword)
                ->get(rtrim($baseUrl, '/').'/GETECHO');

            if ($response->successful()) {
                return true;
            }

            if ($response->status() === 401) {
                $this->wsValidationError = 'Invalid username or password. Please check your credentials.';
            } else {
                $this->wsValidationError = 'Unable to validate credentials. Server returned: '.$response->status();
            }

            return false;
        } catch (\Exception $e) {
            $this->wsValidationError = 'Connection failed: '.$e->getMessage();

            return false;
        }
    }

    /**
     * Skip WorkStudio credentials step.
     */
    public function skipWsStep(): void
    {
        $this->skipWsCredentials = true;
        $this->step = 4;
    }

    /**
     * Step 4: Save theme preference
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

        $this->step = 5;
    }

    /**
     * Step 5: Save dashboard preferences
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

        $this->step = 6;
    }

    /**
     * Step 6: Complete onboarding
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
