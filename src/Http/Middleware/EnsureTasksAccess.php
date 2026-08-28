<?php

namespace Andremellow\Tasks\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureTasksAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        Gate::forUser($request->user())->authorize((string) config('tasks.ability', 'tasks.access'));

        return $next($request);
    }
}
