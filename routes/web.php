<?php

use App\ExecutionTimer;
use App\Livewire\Admin\SyncControl;
use App\Livewire\Admin\SyncHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\UnlinkedPlanners;
use App\Livewire\Admin\AnalyticsSettings;
use App\Livewire\Admin\PlannerManagement;
use App\Livewire\DataManagement\SyncLogs;
use App\Livewire\DataManagement\Endpoints;
use App\Livewire\Assessments\CircuitKanban;
use App\Livewire\DataManagement\Exclusions;
use App\Livewire\Assessments\DataComparison;
use App\Services\WorkStudio\GetQueryService;
use App\Services\WorkStudio\ResourceGroupAccessService;
use App\Livewire\DataManagement\TableManager;
use App\Livewire\Onboarding\OnboardingWizard;
use App\Livewire\Assessments\PlannerAnalytics;
use App\Livewire\DataManagement\CircuitBrowser;
use App\Livewire\Assessments\Dashboard\Overview;
use App\Livewire\DataManagement\Index as DataManagementIndex;

/* Parameters 
|  - full_scope
|  - active_owned
|  - username
|  - active
|  - qc
|  - rewrk
|  - closed
*/
Route::get("/test", function (GetQueryService $queryService) {
    dd($queryService->test());
});

Route::get("/queryAll", function (GetQueryService $queryService) {
    dd($queryService->queryAll());
});

Route::get("/base-data", function (GetQueryService $queryService) {

    $jobguid = '{0D89DA18-2D65-4535-9D7E-F612FB6DA07B}';

    // $data1 = $queryService->getAssessmentsBaseData('full_scope');

    // $data2 = $queryService->getAssessmentsBaseData('active_owned');

    // $data3 = $queryService->getAssessmentsBaseData('username', 'ASPLUNDH\\jfarh');

    // $data4 = $queryService->getJobGuids('full_scope');

    // $data5 = $queryService->getJobGuids('active_owned');

    // $data6 = $queryService->getJobGuids('username', 'ASPLUNDH\\jfarh');

    // $data7 = $queryService->getAssessmentUnits('job_guid', $jobguid);

    // dd($data3);
    // dd($data1, $data2, $data3, $data4, $data5, $data6, $data7);
    return response()->json($data);
});

Route::get('/assessment-jobguids', function (GetQueryService $queryService) {
    $data = $queryService->getJobGuids('username', 'ASPLUNDH\\jfarh');
    dd($data);
    return response()->json($data);
});

Route::get('/units-jobguids', function (GetQueryService $queryService) {
    $jobguid = '{E82D92B3-68E6-43C4-AC8D-35C9DCA1BEA8}';
    $data = $queryService->getAssessmentUnits('job_guid', null, $jobguid);
    dd($data);
    return response()->json($data);
});

// Test route: Get planner circuits with daily records
Route::get('/curl', function (GetQueryService $queryService) {
    $data = $queryService->getPlannerCircuitsWithDailyRecords(
        username: 'ASPLUNDH\cnewcombe',
        contractor: 'Asplundh'
    );

    // dd($data);
    $sum = 0;
    foreach ($data[0]['Daily_Records'] as $record) {
        $sum += $record['Total_Day_Miles'];
    }

    return response()->json($data);
});

Route::get('/curl-units/{jobGuid}', function (GetQueryService $queryService, string $jobGuid) {
    try {
        $data = $queryService->getDailySpanSummary($jobGuid);
        dd($data);

        return response()->json($data);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'jobGuid' => $jobGuid,
        ], 500);
    }
});

Route::get('/curl-basic', function (GetQueryService $queryService) {
    $data = $queryService->getPlannerOwnedCircuits(
        username: 'ASPLUNDH\cnewcombe',
        contractor: 'Asplundh'
    );

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
Route::get('dashboard', fn() => redirect()->route('assessments.overview'))
    ->middleware(['auth', 'verified', 'onboarded'])
    ->name('dashboard');

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

require __DIR__ . '/settings.php';
