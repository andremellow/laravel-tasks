<?php

namespace Andremellow\Tasks\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

class EnsureTasksAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $ability = (string) config('tasks.ability', 'tasks.access');

        if (! Gate::has($ability)) {
            throw new LogicException(
                "The Laravel Tasks package requires the [{$ability}] Gate. "
                .'Define it in the consuming application or replace '
                .'[access_middleware] in config/tasks.php.'
            );
        }

        Gate::forUser($request->user())->authorize($ability);

        return $next($request);
    }
}
