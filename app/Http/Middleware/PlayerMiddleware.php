<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlayerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Allow admins and players through
        if (!auth()->user()->isAdmin() && !auth()->user()->isPlayer()) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
