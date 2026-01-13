<?php

use App\Models\User;
use App\Models\UserWsCredential;
use App\Services\WorkStudio\ApiCredentialManager;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->manager = new ApiCredentialManager;
});

it('returns service account credentials when no user specified', function () {
    $credentials = $this->manager->getCredentials(null);

    expect($credentials['type'])->toBe('service')
        ->and($credentials['user_id'])->toBeNull()
        ->and($credentials['username'])->toBe(config('workstudio.service_account.username'))
        ->and($credentials['password'])->toBe(config('workstudio.service_account.password'));
});

it('returns service account credentials when user has no credentials', function () {
    $user = User::factory()->create();

    $credentials = $this->manager->getCredentials($user->id);

    expect($credentials['type'])->toBe('service');
});

it('returns user credentials when available and valid', function () {
    $user = User::factory()->create();
    UserWsCredential::create([
        'user_id' => $user->id,
        'encrypted_username' => 'DOMAIN\\testuser',
        'encrypted_password' => 'testpass',
        'is_valid' => true,
    ]);

    $credentials = $this->manager->getCredentials($user->id);

    expect($credentials['type'])->toBe('user')
        ->and($credentials['user_id'])->toBe($user->id)
        ->and($credentials['username'])->toBe('DOMAIN\\testuser')
        ->and($credentials['password'])->toBe('testpass');
});

it('falls back to service account when user credentials invalid', function () {
    $user = User::factory()->create();
    UserWsCredential::create([
        'user_id' => $user->id,
        'encrypted_username' => 'DOMAIN\\testuser',
        'encrypted_password' => 'testpass',
        'is_valid' => false,
    ]);

    $credentials = $this->manager->getCredentials($user->id);

    expect($credentials['type'])->toBe('service');
});

it('marks credentials as successful', function () {
    $user = User::factory()->create([
        'ws_credentials_fail_count' => 3,
    ]);
    $credential = UserWsCredential::create([
        'user_id' => $user->id,
        'encrypted_username' => 'DOMAIN\\testuser',
        'encrypted_password' => 'testpass',
        'is_valid' => true,
    ]);

    $this->manager->markSuccess($user->id);

    $credential->refresh();
    $user->refresh();

    expect($credential->is_valid)->toBeTrue()
        ->and($credential->validated_at)->not->toBeNull()
        ->and($credential->last_used_at)->not->toBeNull()
        ->and($user->ws_credentials_fail_count)->toBe(0)
        ->and($user->ws_credentials_last_used_at)->not->toBeNull();
});

it('marks credentials as failed', function () {
    $user = User::factory()->create([
        'ws_credentials_fail_count' => 0,
    ]);
    $credential = UserWsCredential::create([
        'user_id' => $user->id,
        'encrypted_username' => 'DOMAIN\\testuser',
        'encrypted_password' => 'testpass',
        'is_valid' => true,
    ]);

    $this->manager->markFailed($user->id);

    $credential->refresh();
    $user->refresh();

    expect($credential->is_valid)->toBeFalse()
        ->and($user->ws_credentials_fail_count)->toBe(1)
        ->and($user->ws_credentials_failed_at)->not->toBeNull();
});

it('stores new credentials for user', function () {
    $user = User::factory()->create();

    $credential = $this->manager->storeCredentials($user->id, 'DOMAIN\\newuser', 'newpass');

    expect($credential->encrypted_username)->toBe('DOMAIN\\newuser')
        ->and($credential->encrypted_password)->toBe('newpass')
        ->and($credential->is_valid)->toBeTrue();
});

it('updates existing credentials for user', function () {
    $user = User::factory()->create();
    UserWsCredential::create([
        'user_id' => $user->id,
        'encrypted_username' => 'DOMAIN\\olduser',
        'encrypted_password' => 'oldpass',
        'is_valid' => false,
    ]);

    $credential = $this->manager->storeCredentials($user->id, 'DOMAIN\\newuser', 'newpass');

    expect(UserWsCredential::where('user_id', $user->id)->count())->toBe(1)
        ->and($credential->encrypted_username)->toBe('DOMAIN\\newuser')
        ->and($credential->encrypted_password)->toBe('newpass')
        ->and($credential->is_valid)->toBeTrue();
});

it('checks if user has valid credentials', function () {
    $userWithCreds = User::factory()->create();
    UserWsCredential::create([
        'user_id' => $userWithCreds->id,
        'encrypted_username' => 'DOMAIN\\user1',
        'encrypted_password' => 'pass1',
        'is_valid' => true,
    ]);

    $userWithInvalidCreds = User::factory()->create();
    UserWsCredential::create([
        'user_id' => $userWithInvalidCreds->id,
        'encrypted_username' => 'DOMAIN\\user2',
        'encrypted_password' => 'pass2',
        'is_valid' => false,
    ]);

    $userWithNoCreds = User::factory()->create();

    expect($this->manager->hasValidCredentials($userWithCreds->id))->toBeTrue()
        ->and($this->manager->hasValidCredentials($userWithInvalidCreds->id))->toBeFalse()
        ->and($this->manager->hasValidCredentials($userWithNoCreds->id))->toBeFalse();
});

it('reactivates credentials', function () {
    $user = User::factory()->create();
    UserWsCredential::create([
        'user_id' => $user->id,
        'encrypted_username' => 'DOMAIN\\user',
        'encrypted_password' => 'pass',
        'is_valid' => false,
    ]);

    $result = $this->manager->reactivateCredentials($user->id);

    expect($result)->toBeTrue();

    $credential = UserWsCredential::where('user_id', $user->id)->first();
    expect($credential->is_valid)->toBeTrue()
        ->and($credential->validated_at)->toBeNull();
});

it('returns credentials info without exposing password', function () {
    $user = User::factory()->create();
    UserWsCredential::create([
        'user_id' => $user->id,
        'encrypted_username' => 'DOMAIN\\testuser',
        'encrypted_password' => 'testpass',
        'is_valid' => true,
    ]);

    $info = $this->manager->getCredentialsInfo($user->id);

    expect($info['type'])->toBe('user')
        ->and($info['username'])->toBe('DOMAIN\\testuser')
        ->and($info['user_id'])->toBe($user->id)
        ->and($info['is_valid'])->toBeTrue()
        ->and($info)->not->toHaveKey('password');
});

it('does nothing when marking success for null user', function () {
    // Should not throw
    $this->manager->markSuccess(null);
    expect(true)->toBeTrue();
});

it('does nothing when marking failed for null user', function () {
    // Should not throw
    $this->manager->markFailed(null);
    expect(true)->toBeTrue();
});
