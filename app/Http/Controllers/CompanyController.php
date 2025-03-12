<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\BreadType;
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
    ];

    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:companies,name',
        'code' => 'required|string|max:50',
        'type' => 'required|in:invoice,cash',
        'user_ids' => 'required|exists:users,id',
        'mygpm_business_unit' => 'nullable|string|max:255',
        'price_group' => 'required|integer|min:0|max:5' 
  

    ], $messages);

 

        $company = Company::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type'],
            'mygpm_business_unit' => $validated['mygpm_business_unit'],
            'price_group' => $validated['price_group'] 
  

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
    ];

    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:companies,name,'.$company->id,
        'code' => 'required|string|max:50',
        'type' => 'required|in:invoice,cash',
        'user_ids' => 'required|exists:users,id',
        'mygpm_business_unit' => 'nullable|string|max:255',
        'price_group' => 'required|integer|min:0|max:5' 
  

    ],$messages);


        $company->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type'],
            'mygpm_business_unit' => $validated['mygpm_business_unit'],
            'price_group' => $validated['price_group'] 
  

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

    public function manageBreadTypes(Company $company)
{
    // Get all active bread types
    $allBreadTypes = BreadType::where('is_active', true)->get();
    
    // Get the bread types already associated with this company
    $companyBreadTypeIds = $company->breadTypes()->pluck('bread_type_id')->toArray();
    
    return view('companies.manage-bread-types', compact('company', 'allBreadTypes', 'companyBreadTypeIds'));
}

    public function updateBreadTypes(Request $request, Company $company)
{
    $validated = $request->validate([
        'bread_types' => 'nullable|array',
        'bread_types.*' => 'exists:bread_types,id',
    ]);

    // Get current date for valid_from
    $validFrom = now()->toDateString();
    
    // Get existing bread type relationships with their pivot data
    $currentBreadTypesWithPivot = $company->breadTypes()->get()->keyBy('id');
    $currentBreadTypeIds = $currentBreadTypesWithPivot->pluck('id')->toArray();
    
    // Prepare bread types to sync
    $breadTypesToSync = [];
    foreach ($validated['bread_types'] ?? [] as $breadTypeId) {
        $breadType = BreadType::find($breadTypeId);
        
        // Skip if bread type doesn't exist
        if (!$breadType) continue;
        
        // If this is a new relationship, set up the pivot data
        if (!in_array($breadTypeId, $currentBreadTypeIds)) {
            // Calculate price based on company's price group
            $priceInfo = $breadType->getPriceForCompany($company->id);
            
            $breadTypesToSync[$breadTypeId] = [
                'price' => $priceInfo['price'],
                'old_price' => $priceInfo['old_price'],
                'price_group' => $company->price_group,
                'valid_from' => $validFrom,
                'created_by' => auth()->id()
            ];
        } else {
            // For existing relationships, keep existing pivot values
            $existingBreadType = $currentBreadTypesWithPivot[$breadTypeId];
            $breadTypesToSync[$breadTypeId] = [
                'price' => $existingBreadType->pivot->price,
                'old_price' => $existingBreadType->pivot->old_price,
                'price_group' => $existingBreadType->pivot->price_group,
                'valid_from' => $existingBreadType->pivot->valid_from,
                'created_by' => $existingBreadType->pivot->created_by
            ];
        }
    }

    // Sync the bread types with the company
    $company->breadTypes()->sync($breadTypesToSync);

    return redirect()->route('companies.manage-bread-types', $company)
        ->with('success', 'Листата на лебови е успешно ажурирана за ' . $company->name);
}

// public function updateBreadTypes(Request $request, Company $company)
// {
//     $validated = $request->validate([
//         'bread_types' => 'nullable|array',
//         'bread_types.*' => 'exists:bread_types,id',
//     ]);

//     // Get current date for valid_from
//     $validFrom = now()->toDateString();
    
//     // Get existing bread type relationships
//     $currentBreadTypes = $company->breadTypes()->pluck('bread_types.id')->toArray();
    
//     // Prepare bread types to sync
//     $breadTypesToSync = [];
//     foreach ($validated['bread_types'] ?? [] as $breadTypeId) {
//         $breadType = BreadType::find($breadTypeId);
        
//         // Skip if bread type doesn't exist
//         if (!$breadType) continue;
        
//         // If this is a new relationship, set up the pivot data
//         if (!in_array($breadTypeId, $currentBreadTypes)) {
//             // Calculate price based on company's price group
//             $priceInfo = $breadType->getPriceForCompany($company->id);
            
//             $breadTypesToSync[$breadTypeId] = [
//                 'price' => $priceInfo['price'],
//                 'old_price' => $priceInfo['old_price'],
//                 'price_group' => $company->price_group,
//                 'valid_from' => $validFrom,
//                 'created_by' => auth()->id()
//             ];
//         } else {
//             // For existing relationships, just keep them
//             $breadTypesToSync[] = $breadTypeId;
//         }
//     }

//     // Sync the bread types with the company
//     $company->breadTypes()->sync($breadTypesToSync);

//     return redirect()->route('companies.manage-bread-types', $company)
//         ->with('success', 'Листата на лебови е успешно ажурирана за ' . $company->name);
// }


    public function bulkAssignUser(Request $request)
    {
        $validated = $request->validate([
            'from_user_id' => 'required|exists:users,id',
            'to_user_id' => 'required|exists:users,id|different:from_user_id',
        ]);

        $companies = Company::whereHas('users', function($query) use ($validated) {
            $query->where('users.id', $validated['from_user_id']);
        })->get();

        foreach ($companies as $company) {
            $company->users()->sync([$validated['to_user_id']]);
        }

        return redirect()->route('companies.index')
            ->with('success', 'Компаниите се префрлени на новиот корисник.');
    }
}