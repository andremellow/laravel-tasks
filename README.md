# Laravel Tasks

Private reusable task management for Laravel applications. It provides the existing backlog/Kanban UI, task domain actions, persistence, attachments, audit history, and an authenticated JSON API.

## Installation

Add the private repository and package to the consuming application's `composer.json`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "git@github.com:andremellow/laravel-tasks.git" }
  ],
  "require": {
    "andremellow/laravel-tasks": "dev-main"
  }
}
```

For local development with sibling checkouts, prefer:

```json
{
  "type": "path",
  "url": "../laravel-tasks",
  "options": { "symlink": true }
}
```

Then run `composer update andremellow/laravel-tasks` and `php artisan migrate`.

## Host configuration

Publish configuration with `php artisan vendor:publish --tag=tasks-config`. At minimum, configure the authenticatable model and define the single host ability:

```php
'user_model' => App\Models\User::class,
'ability' => 'tasks.access',
```

```php
Gate::define('tasks.access', fn (User $user) => $user->hasPermission(Permission::TasksView));
```

The package's `tasks.access` middleware authorizes that ability. A host can replace `access_middleware` or the complete web/API middleware arrays.

## Standalone or embedded UI

The default `/tasks` page uses the package standalone layout. To render it as a normal page inside the host application:

```php
'layout' => 'layouts.app',
'web' => [
    'prefix' => 'dash-tasks',
    'name' => 'tasks.',
    'middleware' => ['web', 'auth', 'tasks.access'],
],
```

The host owns its menu and may link to `route('tasks.index')` only for authorized users. Package views can be overridden with `php artisan vendor:publish --tag=tasks-views`.

## API

The configurable `/api/tasks` surface supports listing, creation, detail, editing, assignment, status/position changes, soft deletion, and restoration. Authentication is supplied by the host middleware (Sanctum by default).

## Connectors

Implement `Andremellow\Tasks\Contracts\TaskConnector` in the host or a separate provider package. Microsoft To Do and provider OAuth are intentionally not part of this package yet.
