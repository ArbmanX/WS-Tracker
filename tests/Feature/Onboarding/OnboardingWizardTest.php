<?php

use App\Livewire\Onboarding\OnboardingWizard;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->pendingOnboarding()->create([
        'password' => Hash::make('temp-password'),
    ]);
});

// ============================================
// Guest Access Tests
// ============================================

test('guest can access onboarding page', function () {
    $this->get(route('onboarding'))
        ->assertOk()
        ->assertSeeLivewire(OnboardingWizard::class);
});

test('guest sees step 1 on onboarding page', function () {
    Livewire::test(OnboardingWizard::class)
        ->assertSet('step', 1)
        ->assertSee('Enter your credentials');
});

test('guest can authenticate via step 1 with valid credentials', function () {
    Livewire::test(OnboardingWizard::class)
        ->assertSet('step', 1)
        ->set('email', $this->user->email)
        ->set('temporaryPassword', 'temp-password')
        ->call('verifyCredentials')
        ->assertHasNoErrors()
        ->assertSet('step', 2);

    // User should now be authenticated
    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($this->user->id);
});

test('guest with invalid email sees error', function () {
    Livewire::test(OnboardingWizard::class)
        ->assertSet('step', 1)
        ->set('email', 'nonexistent@example.com')
        ->set('temporaryPassword', 'temp-password')
        ->call('verifyCredentials')
        ->assertHasErrors('email')
        ->assertSet('step', 1);
});

test('guest with wrong password sees error', function () {
    Livewire::test(OnboardingWizard::class)
        ->assertSet('step', 1)
        ->set('email', $this->user->email)
        ->set('temporaryPassword', 'wrong-password')
        ->call('verifyCredentials')
        ->assertHasErrors('temporaryPassword')
        ->assertSet('step', 1);
});

test('guest authenticating with already onboarded account sees error', function () {
    $onboardedUser = User::factory()->onboarded()->create([
        'password' => Hash::make('password'),
    ]);

    Livewire::test(OnboardingWizard::class)
        ->assertSet('step', 1)
        ->set('email', $onboardedUser->email)
        ->set('temporaryPassword', 'password')
        ->call('verifyCredentials')
        ->assertHasErrors('email')
        ->assertSee('already been set up')
        ->assertSet('step', 1);
});

// ============================================
// Authenticated User Tests
// ============================================

test('authenticated pending user starts at step 2', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->assertSet('step', 2);
});

test('authenticated pending user can access onboarding page', function () {
    $this->actingAs($this->user)
        ->get(route('onboarding'))
        ->assertOk()
        ->assertSeeLivewire(OnboardingWizard::class);
});

test('onboarded user is redirected away from onboarding', function () {
    $onboardedUser = User::factory()->onboarded()->create();

    $this->actingAs($onboardedUser)
        ->get(route('onboarding'))
        ->assertRedirect(route('dashboard'));
});

test('unonboarded user is redirected to onboarding from dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding'));
});

test('onboarded user can access dashboard', function () {
    $onboardedUser = User::factory()->onboarded()->create();

    // Dashboard route redirects to assessments.overview
    $this->actingAs($onboardedUser)
        ->get(route('dashboard'))
        ->assertRedirect(route('assessments.overview'));
});

// ============================================
// Step 2: Set Password & Name
// ============================================

test('step 2 validates name and new password', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 2)
        ->set('name', '')
        ->set('newPassword', 'short')
        ->set('newPassword_confirmation', 'short')
        ->call('setPassword')
        ->assertHasErrors(['name', 'newPassword']);
});

test('step 2 validates password confirmation', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 2)
        ->set('name', 'Test User')
        ->set('newPassword', 'securepassword123')
        ->set('newPassword_confirmation', 'differentpassword')
        ->call('setPassword')
        ->assertHasErrors('newPassword');
});

test('step 2 updates user and proceeds to step 3', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 2)
        ->set('name', 'New Name')
        ->set('newPassword', 'securepassword123')
        ->set('newPassword_confirmation', 'securepassword123')
        ->call('setPassword')
        ->assertHasNoErrors()
        ->assertSet('step', 3);

    $this->user->refresh();
    expect($this->user->name)->toBe('New Name');
    expect(Hash::check('securepassword123', $this->user->password))->toBeTrue();
});

// ============================================
// Step 3: WorkStudio Credentials
// ============================================

test('step 3 can skip ws credentials', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 3)
        ->call('skipWsStep')
        ->assertSet('skipWsCredentials', true)
        ->assertSet('step', 4);
});

test('step 3 validates ws credentials fields', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 3)
        ->set('wsUsername', '')
        ->set('wsPassword', '')
        ->call('saveWsCredentials')
        ->assertHasErrors(['wsUsername', 'wsPassword']);
});

// ============================================
// Step 4: Theme Selection
// ============================================

test('step 4 saves theme preference', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 4)
        ->set('selectedTheme', 'dark')
        ->call('saveTheme')
        ->assertHasNoErrors()
        ->assertSet('step', 5);

    $this->user->refresh();
    expect($this->user->theme_preference)->toBe('dark');
});

// ============================================
// Step 5: Dashboard Preferences
// ============================================

test('step 5 saves dashboard preferences', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 5)
        ->set('defaultView', 'table')
        ->set('showAllRegions', true)
        ->call('savePreferences')
        ->assertHasNoErrors()
        ->assertSet('step', 6);

    $this->user->refresh();
    expect($this->user->dashboard_preferences['default_view'])->toBe('table');
    expect($this->user->dashboard_preferences['show_all_regions'])->toBeTrue();
});

// ============================================
// Step 6: Complete
// ============================================

test('step 6 completes onboarding and marks user as onboarded', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 6)
        ->call('complete')
        ->assertRedirect(route('dashboard'));

    $this->user->refresh();
    expect($this->user->isOnboarded())->toBeTrue();
    expect($this->user->onboarded_at)->not->toBeNull();
});

// ============================================
// Navigation
// ============================================

test('can navigate back to previous steps', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 3)
        ->call('previousStep')
        ->assertSet('step', 2)
        ->call('previousStep')
        ->assertSet('step', 1);
});

test('cannot go back from step 1', function () {
    Livewire::test(OnboardingWizard::class)
        ->assertSet('step', 1)
        ->call('previousStep')
        ->assertSet('step', 1);
});
