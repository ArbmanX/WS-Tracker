<?php

use App\Livewire\Admin\SyncControl;
use App\Livewire\Admin\SyncHistory;
use App\Livewire\Admin\UnlinkedPlanners;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Assessments\Dashboard\Overview;
use App\Livewire\Onboarding\OnboardingWizard;
use Illuminate\Support\Facades\Route;

// Landing page - redirect based on auth/onboarding status
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isOnboarded()
            ? redirect()->route('dashboard')
            : redirect()->route('onboarding');
    }

    return view('welcome');
})->name('home');

// Onboarding wizard - auth required but NOT onboarded check
Route::get('/onboarding', OnboardingWizard::class)
    ->middleware(['auth'])
    ->name('onboarding');

// Dashboard redirects to assessments overview
Route::get('dashboard', fn () => redirect()->route('assessments.overview'))
    ->middleware(['auth', 'verified', 'onboarded'])
    ->name('dashboard');

// Assessments routes
Route::middleware(['auth', 'verified', 'onboarded'])->prefix('assessments')->name('assessments.')->group(function () {
    Route::get('/overview', Overview::class)->name('overview');
});

// Admin routes (requires admin or sudo_admin role)
Route::middleware(['auth', 'verified', 'onboarded', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sync', SyncControl::class)->name('sync');
    Route::get('/sync/history', SyncHistory::class)->name('sync.history');
    Route::get('/planners/unlinked', UnlinkedPlanners::class)->name('planners.unlinked');
});

// Sudo admin only routes
Route::middleware(['auth', 'verified', 'onboarded', 'sudo_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', UserManagement::class)->name('users');
});

require __DIR__.'/settings.php';
