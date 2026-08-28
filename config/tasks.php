<?php

use Andremellow\Tasks\Http\Middleware\EnsureTasksAccess;

return [
    'user_model' => null,
    'ability' => 'tasks.access',
    'access_middleware' => EnsureTasksAccess::class,
    'layout' => 'tasks::layouts.standalone',
    'web' => [
        'enabled' => true,
        'prefix' => 'tasks',
        'name' => 'tasks.',
        'middleware' => ['web', 'auth', 'tasks.access'],
    ],
    'api' => [
        'enabled' => true,
        'prefix' => 'api/tasks',
        'name' => 'api.tasks.',
        'middleware' => ['api', 'auth:sanctum', 'tasks.access'],
    ],
    'assignee_resolver' => null,
    'user_name_column' => 'name',
    'board' => ['done_limit' => 100],
    'description_max' => 100000,
    'attachment_max_kb' => 10240,
    'attachment_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'txt', 'md', 'csv', 'doc', 'docx', 'odt', 'xls', 'xlsx', 'ods'],
    'media_disk' => null,
    'media_morph_alias' => null,
    'timezone' => 'UTC',
];
