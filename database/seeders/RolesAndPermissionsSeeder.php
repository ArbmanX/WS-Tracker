<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Circuit viewing
            'view-all-circuits' => 'View all circuits across all regions',
            'view-assigned-circuits' => 'View only assigned circuits',
            'view-regional-circuits' => 'View circuits in assigned regions',

            // Workflow management
            'change-workflow-stage' => 'Move circuits between workflow stages',
            'hide-circuits' => 'Hide circuits from default views',

            // Sync operations
            'force-sync' => 'Manually trigger API syncs',
            'view-sync-logs' => 'View sync operation logs',

            // User management
            'manage-users' => 'Create and manage user accounts',
            'link-planners' => 'Link unlinked planners to user accounts',
            'assign-roles' => 'Assign roles to users',

            // Settings
            'manage-settings' => 'Modify application settings',
            'manage-regions' => 'Create and modify regions',

            // Reports
            'view-all-reports' => 'View all reports and dashboards',
            'export-data' => 'Export data from the system',
        ];

        foreach ($permissions as $name => $description) {
            Permission::findOrCreate($name, 'web');
        }

        // Create roles and assign permissions
        $roles = [
            'sudo_admin' => [
                'description' => 'Super administrator with all permissions',
                'permissions' => Permission::all()->pluck('name')->toArray(),
            ],
            'admin' => [
                'description' => 'Administrator with most permissions',
                'permissions' => [
                    'view-all-circuits',
                    'change-workflow-stage',
                    'hide-circuits',
                    'force-sync',
                    'view-sync-logs',
                    'manage-users',
                    'link-planners',
                    'assign-roles',
                    'manage-settings',
                    'manage-regions',
                    'view-all-reports',
                    'export-data',
                ],
            ],
            'general_foreman' => [
                'description' => 'General foreman overseeing multiple regions',
                'permissions' => [
                    'view-all-circuits',
                    'change-workflow-stage',
                    'hide-circuits',
                    'view-sync-logs',
                    'link-planners',
                    'view-all-reports',
                    'export-data',
                ],
            ],
            'planner' => [
                'description' => 'Vegetation planner viewing their circuits',
                'permissions' => [
                    'view-assigned-circuits',
                    'view-regional-circuits',
                ],
            ],
        ];

        foreach ($roles as $name => $config) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($config['permissions']);
        }

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
