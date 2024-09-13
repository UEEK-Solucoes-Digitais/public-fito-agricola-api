<?php

namespace App\Http\Middleware;

class Auth
{

    public function handle($request, \Closure $next, $guard = "admins")
    {
        if (!auth()->guard($guard)->check()) {
            return redirect(route('login.page'));
        }
        return $next($request);
    }
}
