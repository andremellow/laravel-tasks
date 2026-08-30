# Laravel Tasks

Private reusable task management for Laravel applications. It provides a backlog/Kanban UI, task domain actions, persistence, attachments, audit history, and an authenticated JSON API while leaving authentication and access policy under the consuming application's control.

## Requirements

- PHP 8.3+
- Laravel 13
- Livewire 4 and Flux 2
- Spatie Laravel Media Library 11
- An authenticatable Eloquent user model

## Installation from the private repository

Add the private VCS repository and package to the consuming application's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:andremellow/laravel-tasks.git"
    }
  ],
  "require": {
    "andremellow/laravel-tasks": "dev-main"
  }
}
```

Install the package, publish its configuration, and run its migrations:

```shell
composer update andremellow/laravel-tasks
php artisan vendor:publish --tag=tasks-config
php artisan migrate
```

Laravel discovers `Andremellow\Tasks\TasksServiceProvider` automatically. Package migrations are loaded automatically; publishing migrations is not required.

For a private repository in CI or Laravel Cloud, configure Composer/GitHub credentials with read access to `andremellow/laravel-tasks`. Do not commit access tokens to the repository.

### Local package development

For sibling checkouts, replace the VCS repository entry temporarily with a path repository:

```json
{
  "type": "path",
  "url": "../laravel-tasks",
  "options": {
    "symlink": true
  }
}
```

## Required host configuration

Set the authenticatable model in `config/tasks.php`:

```php
'user_model' => App\Models\User::class,
```

The package deliberately does not assume how the host application stores permissions. Its web and API routes use the exported `tasks.access` middleware, which authorizes the configured Laravel Gate:

```php
'ability' => 'tasks.access',
```

The consuming application **must define this Gate**, normally in `AppServiceProvider::boot()`. If the Gate is missing, the middleware fails closed and reports how to configure it.

Import `Gate` and define the rule appropriate for the host:

```php
use Illuminate\Support\Facades\Gate;

// Any authenticated user may access Tasks.
Gate::define('tasks.access', fn ($user): bool => true);
```

```php
// A simple host-specific rule.
Gate::define('tasks.access', fn (User $user): bool => $user->is_admin);
```

```php
// A host with a permission system.
Gate::define(
    'tasks.access',
    fn (User $user): bool => $user->hasPermission('tasks.view'),
);
```

Returning `false` denies access with HTTP 403. The package's internal policies and state-changing actions also delegate to this host ability; hiding a navigation link is not the authorization boundary.

### Replacing the access middleware

The package middleware is exported as:

```php
Andremellow\Tasks\Http\Middleware\EnsureTasksAccess::class
```

It is registered under the `tasks.access` alias. A host that does not use Laravel Gates can replace it in `config/tasks.php`:

```php
'access_middleware' => App\Http\Middleware\EnsureTasksAccess::class,
```

Keep `tasks.access` in both route middleware arrays when replacing only the middleware class. Alternatively, replace the complete arrays:

```php
'web' => [
    'middleware' => ['web', 'auth', App\Http\Middleware\EnsureTasksAccess::class],
],
'api' => [
    'middleware' => ['api', 'auth:sanctum', App\Http\Middleware\EnsureTasksAccess::class],
],
```

Authentication middleware must run before the access middleware so a user is available for authorization.

## Standalone or embedded UI

By default, the package exposes `/tasks` with its standalone layout. Route prefixes, names, middleware, and availability are configurable:

```php
'layout' => 'tasks::layouts.standalone',
'web' => [
    'enabled' => true,
    'prefix' => 'tasks',
    'name' => 'tasks.',
    'middleware' => ['web', 'auth', 'tasks.access'],
],
```

To render Tasks as a normal page inside the host application:

```php
'layout' => 'layouts.app',
'web' => [
    'enabled' => true,
    'prefix' => 'dash-tasks',
    'name' => 'dash-tasks.',
    'middleware' => ['web', 'auth', 'tasks.access'],
],
```

The host owns its navigation and should only display the link to authorized users:

```blade
@can('tasks.access')
    <a href="{{ route('dash-tasks.index') }}">Tasks</a>
@endcan
```

Publish the views only when the host needs to override package markup:

```shell
php artisan vendor:publish --tag=tasks-views
```

## API

The API defaults to `/api/tasks` and uses Sanctum plus the same all-or-nothing access middleware:

```php
'api' => [
    'enabled' => true,
    'prefix' => 'api/tasks',
    'name' => 'api.tasks.',
    'middleware' => ['api', 'auth:sanctum', 'tasks.access'],
],
```

It supports listing, creation, detail, editing, assignment, status/position changes, soft deletion, and restoration. Authentication remains the host application's responsibility. Set `enabled` to `false` to disable either the web surface or API.

## Other configuration

The published `config/tasks.php` also controls the user name column, assignee resolver, completed-task board limit, description and attachment limits, accepted attachment types, media disk, morph alias, and timezone. Task images and videos are private task media; they do not use or expose a shared gallery. Images render as previews and videos use the browser's native player through an authorized package route. Defaults work without publishing for optional values; `user_model` and the access Gate are host responsibilities.

Set both the Laravel filesystem disk and directory prefix used for task media:

```php
'media_disk' => 'local',
'media_path' => 'platform/tasks',
```

Screen recordings default to 512 MB. Because the web UI uses Livewire temporary uploads, the host must set Livewire's temporary upload limit at least as high as `tasks.video_max_kb` and ensure its PHP/web-server request limits allow the same size. For example:

```php
// config/livewire.php
'temporary_file_upload' => [
    'rules' => ['required', 'file', 'max:'.config('tasks.video_max_kb')],
    'max_upload_time' => 30,
],
```

After changing configuration in a cached environment, run:

```shell
php artisan optimize:clear
```

## Updating

Because the package currently tracks `dev-main`, update the consuming application lockfile with:

```shell
composer update andremellow/laravel-tasks
php artisan migrate
```

Commit the consuming application's updated `composer.lock`.

## Connectors

Implement `Andremellow\Tasks\Contracts\TaskConnector` in the host or a separate provider package. Microsoft To Do and provider OAuth are intentionally not part of this package yet.
