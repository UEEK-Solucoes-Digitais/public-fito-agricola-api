<?php

namespace App\Http\Middleware;

use JWAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class Authenticate
{
    public function handle($request, \Closure $next)
    {
        JWTAuth::parseToken()->authenticate();
        return $next($request);
    }
}
