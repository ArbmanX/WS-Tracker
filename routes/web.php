<?php

use App\Livewire\Admin\AnalyticsSettings;
use App\Livewire\Admin\PlannerManagement;
use App\Livewire\Admin\SyncControl;
use App\Livewire\Admin\SyncHistory;
use App\Livewire\Admin\UnlinkedPlanners;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Assessments\CircuitKanban;
use App\Livewire\Assessments\Dashboard\Overview;
use App\Livewire\Assessments\DataComparison;
use App\Livewire\Assessments\PlannerAnalytics;
use App\Livewire\DataManagement\CircuitBrowser;
use App\Livewire\DataManagement\Endpoints;
use App\Livewire\DataManagement\Exclusions;
use App\Livewire\DataManagement\Index as DataManagementIndex;
use App\Livewire\DataManagement\SyncLogs;
use App\Livewire\DataManagement\TableManager;
use App\Livewire\MapDemo;
use App\Livewire\Onboarding\OnboardingWizard;
use App\Services\WorkStudio\GetQueryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/* Parameters
|  - full_scope
|  - active_owned
|  - username
|  - active
|  - qc
|  - rewrk
|  - closed
*/

Route::get('/assessment-jobguids', function (GetQueryService $queryService) {
    $data = $queryService->getJobGuids();
    dd($data);

    return response()->json($data);
});

Route::get('/system-wide-metrics', function (GetQueryService $queryService) {
    $data = $queryService->getSystemWideMetrics();
    dd($data->first());

    return response()->json($data);
});

Route::get('/regional-metrics', function (GetQueryService $queryService) {
    $data = $queryService->getRegionalMetrics();
    dd($data);

    return response()->json($data);
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

// Landing page - redirect based on auth/onboarding status
Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->isOnboarded()
            ? redirect()->route('dashboard')
            : redirect()->route('onboarding');
    }

    return view('welcome');
})->name('home');

Route::view('/asstest', 'assessments.index')->name('asstest');

// Temporary route for testing - no auth required
Route::get('/data-comparison', DataComparison::class)->name('assessments.data-comparison');

// Onboarding wizard - accessible to guests (for first-time setup) and pending users
Route::get('/onboarding', OnboardingWizard::class)
    ->middleware(['guest.or.pending'])
    ->name('onboarding');

// Dashboard redirects to assessments overview
Route::get('dashboard', fn () => redirect()->route('assessments.overview'))
    ->middleware(['auth', 'verified', 'onboarded'])
    ->name('dashboard');

// Map demo (Leafwire test page)
Route::get('/map-demo', MapDemo::class)
    ->middleware(['auth', 'verified', 'onboarded'])
    ->name('map-demo');

// Assessments routes
Route::middleware(['auth', 'verified', 'onboarded'])->prefix('assessments')->name('assessments.')->group(function () {
    Route::get('/overview', Overview::class)->name('overview');
    Route::get('/kanban', CircuitKanban::class)->name('kanban');
    Route::get('/planner-analytics', PlannerAnalytics::class)->name('planner-analytics');
});

// Admin routes (requires admin or sudo_admin role)
Route::middleware(['auth', 'verified', 'onboarded', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sync', SyncControl::class)->name('sync');
    Route::get('/sync/history', SyncHistory::class)->name('sync.history');
    Route::get('/planners', PlannerManagement::class)->name('planners');
    Route::get('/planners/unlinked', UnlinkedPlanners::class)->name('planners.unlinked');
});

// Sudo admin only routes
Route::middleware(['auth', 'verified', 'onboarded', 'sudo_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', UserManagement::class)->name('users');
    Route::get('/analytics-settings', AnalyticsSettings::class)->name('analytics-settings');

    // Data Management
    Route::get('/data', DataManagementIndex::class)->name('data');
    Route::get('/data/circuits', CircuitBrowser::class)->name('data.circuits');
    Route::get('/data/sync-logs', SyncLogs::class)->name('data.sync-logs');
    Route::get('/data/exclusions', Exclusions::class)->name('data.exclusions');
    Route::get('/data/endpoints', Endpoints::class)->name('data.endpoints');
    Route::get('/data/tables', TableManager::class)->name('data.tables');
});

require __DIR__.'/settings.php';
