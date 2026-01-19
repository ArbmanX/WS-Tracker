<?php

use App\Services\Sync\SyncOutputLogger;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Clear any existing cache entries before each test
    Cache::flush();
});

it('initializes with default idle state', function () {
    $logger = new SyncOutputLogger('test_session');

    $state = $logger->getState();

    expect($state['status'])->toBe('idle');
    expect($state['current_item'])->toBe(0);
    expect($state['total_items'])->toBe(0);
    expect($state['error_count'])->toBe(0);
});

it('can start a sync session', function () {
    $logger = new SyncOutputLogger('test_session');

    $logger->start('Starting test sync');

    $state = $logger->getState();

    expect($state['status'])->toBe('running');
    expect($state['description'])->toBe('Starting test sync');
    expect($state['current_operation'])->toBe('Starting test sync');
    expect($state['started_at'])->not->toBeNull();
});

it('logs info messages', function () {
    $logger = new SyncOutputLogger('test_session');
    $logger->start('Test');

    $logger->info('This is an info message');

    $logs = $logger->getLogs();

    expect($logs)->toHaveCount(2); // start message + info message
    expect($logs[1]['level'])->toBe('info');
    expect($logs[1]['message'])->toBe('This is an info message');
    expect($logs[1]['timestamp'])->toMatch('/^\d{2}:\d{2}:\d{2}$/');
});

it('logs success messages', function () {
    $logger = new SyncOutputLogger('test_session');

    $logger->success('Operation succeeded');

    $logs = $logger->getLogs();

    expect($logs)->toHaveCount(1);
    expect($logs[0]['level'])->toBe('success');
    expect($logs[0]['message'])->toBe('Operation succeeded');
});

it('logs warning messages', function () {
    $logger = new SyncOutputLogger('test_session');

    $logger->warning('This is a warning');

    $logs = $logger->getLogs();

    expect($logs[0]['level'])->toBe('warning');
});

it('logs error messages and increments error count', function () {
    $logger = new SyncOutputLogger('test_session');
    $logger->start('Test');

    $logger->error('An error occurred');
    $logger->error('Another error');

    $state = $logger->getState();
    $logs = $logger->getLogs();

    expect($state['error_count'])->toBe(2);
    expect($logs)->toHaveCount(3); // start + 2 errors
});

it('tracks progress', function () {
    $logger = new SyncOutputLogger('test_session');
    $logger->start('Test');

    $logger->progress(5, 100, 'Processing item 5');

    $state = $logger->getState();

    expect($state['current_item'])->toBe(5);
    expect($state['total_items'])->toBe(100);
    expect($state['current_operation'])->toBe('Processing item 5');
});

it('can set current operation', function () {
    $logger = new SyncOutputLogger('test_session');
    $logger->start('Test');

    $logger->setOperation('Now doing something else');

    $state = $logger->getState();

    expect($state['current_operation'])->toBe('Now doing something else');
});

it('marks sync as completed', function () {
    $logger = new SyncOutputLogger('test_session');
    $logger->start('Test');

    $logger->complete('All done!');

    $state = $logger->getState();
    $logs = $logger->getLogs();

    expect($state['status'])->toBe('completed');
    expect($state['completed_at'])->not->toBeNull();
    expect($logs[count($logs) - 1]['level'])->toBe('success');
    expect($logs[count($logs) - 1]['message'])->toBe('All done!');
});

it('marks sync as failed', function () {
    $logger = new SyncOutputLogger('test_session');
    $logger->start('Test');

    $logger->fail('Something went wrong');

    $state = $logger->getState();

    expect($state['status'])->toBe('failed');
    expect($state['completed_at'])->not->toBeNull();
    expect($state['error_count'])->toBe(1);
});

it('can get logs since a specific index', function () {
    $logger = new SyncOutputLogger('test_session');

    $logger->info('First');
    $logger->info('Second');
    $logger->info('Third');

    $newLogs = $logger->getLogsSince(1);

    expect($newLogs)->toHaveCount(2);
    expect($newLogs[0]['message'])->toBe('Second');
    expect($newLogs[1]['message'])->toBe('Third');
});

it('returns combined output', function () {
    $logger = new SyncOutputLogger('test_session');
    $logger->start('Test');
    $logger->info('Message 1');
    $logger->info('Message 2');

    $output = $logger->getOutput();

    expect($output)->toHaveKeys(['state', 'logs', 'log_count']);
    expect($output['state']['status'])->toBe('running');
    expect($output['log_count'])->toBe(3); // start + 2 info
});

it('correctly reports running status', function () {
    $logger = new SyncOutputLogger('test_session');

    expect($logger->isRunning())->toBeFalse();

    $logger->start('Test');

    expect($logger->isRunning())->toBeTrue();

    $logger->complete('Done');

    expect($logger->isRunning())->toBeFalse();
});

it('clears all logs and state', function () {
    $logger = new SyncOutputLogger('test_session');
    $logger->start('Test');
    $logger->info('Some message');

    $logger->clear();

    expect($logger->getLogs())->toBe([]);
    expect($logger->getState()['status'])->toBe('idle');
});

it('creates logger for specific sync log id', function () {
    $logger = SyncOutputLogger::forSyncLog(123);

    $logger->info('Test message');

    // Verify it uses a unique cache key
    expect(Cache::get('sync_output:sync_log_123:logs'))->not->toBeNull();
});

it('creates logger for specific user', function () {
    $logger = SyncOutputLogger::forUser(456);

    $logger->info('Test message');

    // Verify it uses a unique cache key
    expect(Cache::get('sync_output:user_456:logs'))->not->toBeNull();
});

it('limits log entries to prevent memory issues', function () {
    $logger = new SyncOutputLogger('test_session');

    // Add more than the max entries (500)
    for ($i = 0; $i < 550; $i++) {
        $logger->info("Message {$i}");
    }

    $logs = $logger->getLogs();

    expect(count($logs))->toBe(500);
    // First message should be trimmed
    expect($logs[0]['message'])->toBe('Message 50');
});

it('isolates sessions by key', function () {
    $logger1 = new SyncOutputLogger('session_1');
    $logger2 = new SyncOutputLogger('session_2');

    $logger1->info('Message for session 1');
    $logger2->info('Message for session 2');

    expect($logger1->getLogs())->toHaveCount(1);
    expect($logger1->getLogs()[0]['message'])->toBe('Message for session 1');

    expect($logger2->getLogs())->toHaveCount(1);
    expect($logger2->getLogs()[0]['message'])->toBe('Message for session 2');
});
