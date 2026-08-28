<?php

namespace Andremellow\Tasks;

use Andremellow\Tasks\Enums\Permission;
use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Policies\TaskPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TasksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tasks.php', 'tasks');
    }

    public function boot(Router $router): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tasks');
        Livewire::addNamespace('tasks', viewPath: __DIR__.'/../resources/views');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $router->aliasMiddleware('tasks.access', config('tasks.access_middleware'));

        Gate::policy(Task::class, TaskPolicy::class);
        $mediaMorphAlias = config('tasks.media_morph_alias') ?: Task::class;
        Relation::morphMap([$mediaMorphAlias => Task::class], merge: true);
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn ($user): bool => Gate::forUser($user)->allows((string) config('tasks.ability', 'tasks.access')));
        }

        if (config('tasks.web.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
        if (config('tasks.api.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        $this->publishes([__DIR__.'/../config/tasks.php' => config_path('tasks.php')], 'tasks-config');
        $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/tasks')], 'tasks-views');
    }
}
