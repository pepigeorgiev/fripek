<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
{
    $query = Company::with('users');
    
    // Filter by user
    if ($request->has('user_id') && $request->user_id != 'all') {
        $query->whereHas('users', function($q) use ($request) {
            $q->where('users.id', $request->user_id);
        });
    }
    
    // Search functionality
    if ($request->has('search') && $request->search != '') {
        $query->where(function($q) use ($request) {
            $q->where('name', 'like', '%'.$request->search.'%')
              ->orWhere('code', 'like', '%'.$request->search.'%');
        });
    }
    
    $companies = $query->get();
    $users = User::where('role', '!=', 'admin')->get();
    
    return view('companies.index', compact('companies', 'users'));
}


    public function store(Request $request)
{
    $messages = [
        'name.unique' => 'Имате внесено компанија со исто име.',
        'code.unique' => 'Имате внесено иста шифра.',
    ];

    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:companies,name',
        'code' => 'required|string|max:50|unique:companies,code',
        'type' => 'required|in:invoice,cash',
        'user_ids' => 'required|exists:users,id'
    ], $messages);

 

        $company = Company::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type']
        ]);

        // Attach single user
        $company->users()->attach($validated['user_ids']);

        return redirect()->route('companies.index')
            ->with('success', 'Компанијата е креирана.');
    }


    public function update(Request $request, Company $company)
{

    $messages = [
        'name.unique' => 'Имате внесено компанија со исто име.',
        'code.unique' => 'Имате внесено иста шифра.',
    ];

    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:companies,name,'.$company->id,
        'code' => 'required|string|max:50|unique:companies,code,'.$company->id,
        'type' => 'required|in:invoice,cash',
        'user_ids' => 'required|exists:users,id'
    ],$messages);


        $company->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type']
        ]);

        // Sync single user
        $company->users()->sync([$validated['user_ids']]);

        return redirect()->route('companies.index')
            ->with('success', 'Компанијата е ажурирана.');
    }


    public function confirmDelete(Company $company)
    {
        return view('companies.confirm-delete', compact('company'));
    }

    public function destroy(Company $company)
    {
        try {
            $company->delete();
            return redirect()->route('companies.index')
                ->with('success', 'Компанијата е избришана');
        } catch (\Exception $e) {
            return redirect()->route('companies.index')
                ->with('error', 'Не можете да ја избришете компанијата. Веке имате зачувано трансакции на истата');
        }
    }
}