<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class CompanyAssignmentController extends Controller
{
    public function index()
    {
        $users = User::with('companies')->get();
        $allCompanies = Company::all();
        
        return view('admin.company-assignments', compact('users', 'allCompanies'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'companies' => 'array',
            'companies.*' => 'exists:companies,id'
        ]);

        $user->companies()->sync($request->companies);

        return redirect()->back()->with('success', 'Company assignments updated successfully');
    }
}
