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

test('onboarding page loads for unonboarded user', function () {
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

test('step 1 validates temporary password', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->assertSet('step', 1)
        ->set('temporaryPassword', 'wrong-password')
        ->call('verifyCredentials')
        ->assertHasErrors('temporaryPassword')
        ->assertSet('step', 1);
});

test('step 1 proceeds to step 2 with correct password', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->assertSet('step', 1)
        ->set('temporaryPassword', 'temp-password')
        ->call('verifyCredentials')
        ->assertHasNoErrors()
        ->assertSet('step', 2);
});

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

test('step 3 saves theme preference', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 3)
        ->set('selectedTheme', 'dark')
        ->call('saveTheme')
        ->assertHasNoErrors()
        ->assertSet('step', 4);

    $this->user->refresh();
    expect($this->user->theme_preference)->toBe('dark');
});

test('step 4 saves dashboard preferences', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 4)
        ->set('defaultView', 'table')
        ->set('showAllRegions', true)
        ->call('savePreferences')
        ->assertHasNoErrors()
        ->assertSet('step', 5);

    $this->user->refresh();
    expect($this->user->dashboard_preferences['default_view'])->toBe('table');
    expect($this->user->dashboard_preferences['show_all_regions'])->toBeTrue();
});

test('step 5 completes onboarding and marks user as onboarded', function () {
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->set('step', 5)
        ->call('complete')
        ->assertRedirect(route('dashboard'));

    $this->user->refresh();
    expect($this->user->isOnboarded())->toBeTrue();
    expect($this->user->onboarded_at)->not->toBeNull();
});

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
    Livewire::actingAs($this->user)
        ->test(OnboardingWizard::class)
        ->assertSet('step', 1)
        ->call('previousStep')
        ->assertSet('step', 1);
});

test('guest cannot access onboarding', function () {
    $this->get(route('onboarding'))
        ->assertRedirect(route('login'));
});
