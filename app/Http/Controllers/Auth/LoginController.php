<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Get the authenticated user
            $user = Auth::user();
            
            // Redirect based on user role
            if ($user->isAdmin() || $user->isSuperAdmin()) {
                return redirect()->intended('dashboard');
            }
            
            // Regular users go to daily transactions create page
            return redirect()->route('daily-transactions.create');
        }

        return back()->withErrors([
            'email' => 'Невалидни кориснички податоци.',
        ]);
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->role === 'user') {
            return redirect()->route('daily-transactions.create');
        }
        
        // Admin users (admin-admin, admin_user, super_admin) go to dashboard
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');
    }
}