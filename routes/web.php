<?php

use App\Livewire\Admin\SyncControl;
use App\Livewire\Admin\SyncHistory;
use App\Livewire\Admin\UnlinkedPlanners;
use App\Livewire\Admin\UserManagement;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Shell test route (temporary - for testing new app shell layout)
Route::view('shell-test', 'pages.shell-test')
    ->middleware(['auth', 'verified'])
    ->name('shell-test');

// Admin routes (requires admin or sudo_admin role)
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sync', SyncControl::class)->name('sync');
    Route::get('/sync/history', SyncHistory::class)->name('sync.history');
    Route::get('/planners/unlinked', UnlinkedPlanners::class)->name('planners.unlinked');
});

// Sudo admin only routes
Route::middleware(['auth', 'verified', 'sudo_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', UserManagement::class)->name('users');
});

require __DIR__.'/settings.php';
