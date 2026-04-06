<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, ['admin', 'superadmin'], true)) {
            return response()->json([
                'data' => null,
                'message' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}
