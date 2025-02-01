<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                // Admin users see dashboard
                if ($user->role === 'admin-admin' || $user->role === 'admin_user') {
                    return redirect('/dashboard');
                }

                // Regular users see daily transactions
                if ($user->role === 'user') {
                    return redirect('/daily-transactions/create');
                }
            }
        }

        return $next($request);
    }
}
