<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                // Super Admin and Admin see dashboard
                if ($user->role === User::ROLE_SUPER_ADMIN || $user->role === User::ROLE_ADMIN) {
                    return $request->expectsJson()
                        ? response()->json(['redirect' => '/dashboard'])
                        : redirect('/dashboard');
                }

                // Regular users see daily transactions
                if ($user->role === User::ROLE_USER) {
                    return $request->expectsJson()
                        ? response()->json(['redirect' => '/daily-transactions/create'])
                        : redirect('/daily-transactions/create');
                }
            }
        }

        return $next($request);
    }
} 