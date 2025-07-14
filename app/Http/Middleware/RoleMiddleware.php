<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Role hierarchy check
        $hasAccess = match($role) {
            'admin' => $user->isAdmin(),
            'ssd' => $user->isSSD(),
            'security' => $user->isSecurity(),
            'user' => true, // All authenticated users can access user-level content
            default => false
        };

        if (!$hasAccess) {
            abort(403, 'Access denied. Insufficient permissions.');
        }

        return $next($request);
    }
}