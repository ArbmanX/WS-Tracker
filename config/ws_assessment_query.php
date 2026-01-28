<?php

return [
    "scope_year"=> "2026",

    'contractors' => [
       'Asplundh'
    ],

    'excludedUsers' => [
        'ASPLUNDH\\jcompton',
        'ASPLUNDH\\joseam',
    ],

    'job_types' => [
        'Assessment',
        'Assessment Dx',
        'Split_Assessment',
        'Tandem_Assessment',
    ],

    'resourceGroups' => [

        'all' => [
            // Geographic/Operational
            'CENTRAL',
            'HARRISBURG',
            'LEHIGH',
            'LANCASTER',
            'DISTRIBUTION',

            // Planner Groups
            'PRE_PLANNER',
            'VEG_ASSESSORS',
            'VEG_PLANNERS',

            // Crew Groups (not accessible to planners)
            'VEG_CREW',
            'VEG_FOREMAN',
        ],

        'default' => [
            'DISTRIBUTION',
            'VEG_PLANNER',
        ],

        'roles' => [
            'planner' => [
                'CENTRAL',
                'HARRISBURG',
                'LEHIGH',
                'LANCASTER',
                'DISTRIBUTION',
                'PRE_PLANNER',
                'VEG_ASSESSORS',
                'VEG_PLANNERS',
            ],

            '*' => config('workstudio_resource_groups.all'),
            'admin' => config('workstudio_resource_groups.all'),      // Full access
            'sudo_admin' => config('workstudio_resource_groups.all'), // Full access
        ],

        /*
            |--------------------------------------------------------------------------
            | User-Specific Region Restrictions (Optional)
            |--------------------------------------------------------------------------
            | Override role-based access for specific users.
            | Useful when a planner is limited to certain geographic regions.
            */

        'users' => [
            'Adam Miller' => ['LANCASTER', 'HARRISBURG'],
        ],

    ],

    'statuses' => [

        'planner_concern' => [ 'ACTIV', 'QC', 'REWRK', 'CLOSE', ],

        'all' => [
            'new' => [
                'value' => 'SA',
                'caption' => 'New'
            ],

            'active' => [
                'value' => 'ACTIV',
                'caption' => 'In Progress'
            ],

            'qc' => [
                'value' => 'QC',
                'caption' =>
                'Pending Quality Control - You Will Be Notified If Any Changes Made. 
                  -- Any Units Are Failed, Or If It Is Sent To Rework,
                  You Will Be Notified As Well'
            ],

            'rework' => [
                'value' => 'REWRK',
                'caption' =>
                'Sent To Rework - Check Audit Notes & Pending Permissions'
            ],

            'deferral' => ['value' => 'DEF', 'caption' => 'Deferral'],

            'closed' => ['value' => 'CLOSE', 'caption' => 'Closed'],
        ]
    ],
];
