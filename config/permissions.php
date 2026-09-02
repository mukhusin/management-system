<?php

use App\Enums\UserRole;

/*
|--------------------------------------------------------------------------
| Permission catalog + role defaults
|--------------------------------------------------------------------------
|
| Every key in "catalog" is registered as a Laravel Gate ability in
| AppServiceProvider. A user is granted an ability if their role's default
| set includes it, unless a row in `permission_user` overrides it
| (granted = true/false). system_admin is allowed everything via Gate::before.
|
*/

return [

    'catalog' => [
        'tenders.ingest' => 'Run / configure automated tender ingestion',
        'tenders.create' => 'Register tenders manually',
        'tenders.edit' => 'Edit tender details',
        'tenders.edit_baseline' => 'Edit tender financial value / deadline (audited)',
        'tenders.transition' => 'Move a tender through its lifecycle states',
        'tenders.comment' => 'Comment on tenders and project work',
        'service_requests.create' => 'Log inbound service requests',
        'service_requests.edit' => 'Edit service request details',
        'service_requests.transition' => 'Move a service request through its lifecycle',
        'services.manage' => 'Manage the service-line catalog',
        'projects.initiate' => 'Promote a won tender / service request into a project, create projects',
        'projects.edit' => 'Edit projects, advance SDLC phases',
        'projects.edit_baseline' => 'Edit project budget / target deadline (audited)',
        'projects.manage_work' => 'Create & assign milestones, feature sets, tasks',
        'work.execute' => 'Update status / progress on assigned tasks & sub-tasks',
        'tracker.manage' => 'Create & edit generic tracker items',
        'users.manage' => 'Manage user accounts, roles and permissions',
        'config.manage' => 'Manage global configuration',
        'audit.view' => 'View the audit log',
    ],

    'roles' => [
        UserRole::SystemAdmin->value => ['*'],

        UserRole::TenderOfficer->value => [
            'tenders.ingest', 'tenders.create', 'tenders.edit', 'tenders.edit_baseline',
            'tenders.transition', 'tenders.comment', 'tracker.manage',
            'service_requests.create', 'service_requests.edit', 'service_requests.transition',
        ],

        UserRole::ProjectManager->value => [
            'tenders.comment', 'projects.initiate', 'projects.edit', 'projects.edit_baseline',
            'projects.manage_work', 'work.execute', 'tracker.manage', 'audit.view',
            'service_requests.create', 'service_requests.edit', 'service_requests.transition',
        ],

        UserRole::DevMember->value => [
            'tenders.comment', 'work.execute',
        ],
    ],
];
