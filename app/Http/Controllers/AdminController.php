<?php



namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller 
{
    public function __construct()
    {
        // Add middleware to check if user can manage other users
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin()) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $user = auth()->user();
        
        // Super admin can see all users
        if ($user->isSuperAdmin()) {
            $users = User::with('companies')->get();
        } else {
            // Regular admin can only see regular users
            $users = User::with('companies')
                        ->where('role', User::ROLE_USER)
                        ->get();
        }
        
        $companies = Company::all();
        return view('users.index', compact('users', 'companies'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:' . implode(',', [User::ROLE_ADMIN, User::ROLE_USER]),
        ]);

        // Only super admin can create admin users
        if ($validated['role'] === User::ROLE_ADMIN && !$user->canManageAdmins()) {
            abort(403, 'Unauthorized action.');
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }


    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|unique:users,name,' . $user->id,
            'password' => 'nullable|min:6'
        ]);

        $user->name = $validated['name'];
        if ($validated['password']) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return redirect()->back()->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->back()->with('success', 'User deleted successfully');
    }
}