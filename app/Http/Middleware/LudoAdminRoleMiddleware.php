<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LudoAdminRoleMiddleware
{
    /**
     * Handle an incoming request with role verification.
     * Allowed roles: 'admin', 'superadmin', 'support_staff', 'moderator'
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $permissionGroup
     */
    public function handle(Request $request, Closure $next, ?string $permissionGroup = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access the administration console.');
        }

        // Account active check
        if ($user->status === 'suspended') {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your administrative account has been suspended.');
        }

        $role = $user->role;

        // Superadmin and Admin have universal access
        if (in_array($role, ['admin', 'superadmin'])) {
            return $next($request);
        }

        // Specific sub-role checks
        if ($permissionGroup === 'support' && in_array($role, ['support_staff', 'moderator'])) {
            return $next($request);
        }

        if ($permissionGroup === 'moderator' && $role === 'moderator') {
            return $next($request);
        }

        abort(403, 'Unauthorized: Access restricted to authorized platform administrators.');
    }
}
