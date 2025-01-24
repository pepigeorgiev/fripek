<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;  
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function manage()
    {
        // Check if user is super admin
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        
        $users = User::where('id', '!=', auth()->id())->get();
        return view('users.manage', compact('users'));
    }



    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:' . implode(',', [
                User::ROLE_USER,
                User::ROLE_ADMIN,
                User::ROLE_SUPER_ADMIN
            ]),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        session()->flash('new_user_password', $validated['password']);
        session()->flash('new_user_email', $user->email);

        return redirect()->back()->with('success', 'Корисникот е успешно креиран');
    }

    public function resetPassword(User $user)
    {
        \Log::info('Password reset attempt for user: ' . $user->id);
        
        if (!auth()->user()->isSuperAdmin()) {
            \Log::warning('Non-super admin attempted to reset password');
            return response()->json([
                'success' => false,
                'message' => 'Неовластен пристап.'
            ], 403);
        }

        try {
            \Log::info('Generating new password for user: ' . $user->name);
            
            // Get user's name and convert to lowercase
            $name = strtolower($user->name);
            // Remove any spaces and special characters
            $name = preg_replace('/[^a-z0-9]/', '', $name);
            
            // Create a simple password: username + random 3 digits
            $randomDigits = rand(100, 999);
            $newPassword = $name . $randomDigits;
            
            \Log::info('New password generated: ' . $newPassword);
            
            $user->update([
                'password' => Hash::make($newPassword)
            ]);

            \Log::info('Password successfully updated for user: ' . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Лозинката е ресетирана успешно',
                'password' => $newPassword
            ]);
        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Грешка при ресетирање на лозинката: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePassword(Request $request, User $user)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Неовластен пристап.'
            ], 403);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        try {
            $user->update([
                'password' => Hash::make($validated['password']),
                'plain_password' => $validated['password']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Лозинката е успешно променета'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Грешка при промена на лозинката: ' . $e->getMessage()
            ], 500);
        }
    }


    

    public function destroy(User $user)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user->delete();
        return redirect()->route('users.manage')->with('success', 'Корисникот е избришан');
    }

    


    public function dashboard()
    {
        $user = auth()->user();
        // Get companies associated with the logged-in user
        $userCompanies = $user->companies;
        
        return view('users.dashboard', compact('userCompanies'));
    }

  

    public function getCompanies(User $user)
    {
        return response()->json($user->companies->pluck('id'));
    }

    public function updateCompanies(Request $request, User $user)
    {
        $validated = $request->validate([
            'companies' => 'array',
            'companies.*' => 'exists:companies,id'
        ]);

        $user->companies()->sync($request->companies ?? []);
        return redirect()->route('users.index')->with('success', 'Companies assigned successfully');
    }
}