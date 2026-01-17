<?php

use App\Livewire\Admin\UserManagement;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('user management page renders for sudo_admin', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertSeeLivewire(UserManagement::class);
});

test('displays users list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    User::factory()->count(3)->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->assertViewHas('users', fn ($users) => $users->count() === 4); // 3 + admin
});

test('can search users by name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    User::factory()->create(['name' => 'John Smith']);
    User::factory()->create(['name' => 'Jane Doe']);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->set('search', 'John')
        ->assertViewHas('users', fn ($users) => $users->count() === 1);
});

test('can search users by email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    User::factory()->create(['email' => 'john@example.com']);
    User::factory()->create(['email' => 'jane@example.com']);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->set('search', 'john@')
        ->assertViewHas('users', fn ($users) => $users->count() === 1);
});

test('can filter by role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $planner = User::factory()->create();
    $planner->assignRole('planner');

    $anotherAdmin = User::factory()->create();
    $anotherAdmin->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->set('roleFilter', 'planner')
        ->assertViewHas('users', fn ($users) => $users->count() === 1);
});

test('can open create modal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->assertSet('showModal', false)
        ->call('create')
        ->assertSet('showModal', true)
        ->assertSet('editingUserId', null);
});

test('can open edit modal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $user = User::factory()->create(['name' => 'Test User']);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('edit', $user->id)
        ->assertSet('showModal', true)
        ->assertSet('editingUserId', $user->id)
        ->assertSet('name', 'Test User');
});

test('can create new user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('create')
        ->set('name', 'New User')
        ->set('email', 'newuser@example.com')
        ->set('password', 'password123')
        ->set('selectedRole', 'planner')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertDispatched('notify');

    expect(User::where('email', 'newuser@example.com')->exists())->toBeTrue();
    $newUser = User::where('email', 'newuser@example.com')->first();
    expect($newUser->hasRole('planner'))->toBeTrue();
});

test('validates required fields on create', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('create')
        ->set('name', '')
        ->set('email', '')
        ->set('password', '')
        ->call('save')
        ->assertHasErrors(['name', 'email', 'password', 'selectedRole']);
});

test('validates unique email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    User::factory()->create(['email' => 'existing@example.com']);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('create')
        ->set('name', 'New User')
        ->set('email', 'existing@example.com')
        ->set('password', 'password123')
        ->set('selectedRole', 'planner')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('can update user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $user = User::factory()->create(['name' => 'Old Name']);
    $user->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('edit', $user->id)
        ->set('name', 'New Name')
        ->call('save')
        ->assertSet('showModal', false);

    expect($user->refresh()->name)->toBe('New Name');
});

test('password is optional when editing', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
    $user->assignRole('planner');
    $oldPasswordHash = $user->password;

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('edit', $user->id)
        ->set('name', 'Updated Name')
        ->set('password', '') // Leave empty
        ->call('save');

    expect($user->refresh()->password)->toBe($oldPasswordHash);
});

test('can update user password', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $user = User::factory()->create();
    $user->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('edit', $user->id)
        ->set('password', 'newpassword123')
        ->call('save');

    expect(Hash::check('newpassword123', $user->refresh()->password))->toBeTrue();
});

test('can delete user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('delete', $user->id)
        ->assertDispatched('notify');

    expect(User::find($user->id))->toBeNull();
});

test('cannot delete own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('delete', $admin->id)
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'cannot delete your own');
        });

    expect(User::find($admin->id))->not->toBeNull();
});

test('can close modal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('name', '')
        ->assertSet('email', '');
});

test('can set default region for user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('create')
        ->set('name', 'New User')
        ->set('email', 'user@example.com')
        ->set('password', 'password123')
        ->set('selectedRole', 'planner')
        ->set('defaultRegionId', $region->id)
        ->call('save');

    $newUser = User::where('email', 'user@example.com')->first();
    expect($newUser->default_region_id)->toBe($region->id);
});
