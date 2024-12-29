<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JWTAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $request->merge(['user' => $user]);

        return $next($request);
    }
}
