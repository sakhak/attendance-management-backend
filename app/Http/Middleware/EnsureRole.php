<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user('sanctum');

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        // super_admin bypass
        if ($user->roles()->where('key', 'super_admin')->exists()) {
            return $next($request);
        }

        // check allowed roles
        if (!$user->roles()->whereIn('key', $roles)->exists()) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
