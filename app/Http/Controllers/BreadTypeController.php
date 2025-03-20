<?php



namespace App\Http\Controllers;

use App\Models\BreadType;
use App\Models\BreadPriceHistory;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BreadTypeController extends Controller
{
    public function index()
    {
        $breadTypes = BreadType::latest()->get();
        return view('bread-types.index', compact('breadTypes'));
    }

    public function create()
    {
        return view('bread-types.create');
    }

    public function store(Request $request)
{

    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bread_types',
            'code' => 'required|string|max:50|unique:bread_types',
            'price' => 'required|numeric|min:0',
            'price_group_1' => 'nullable|numeric|min:0',
            'price_group_2' => 'nullable|numeric|min:0',
            'price_group_3' => 'nullable|numeric|min:0',
            'price_group_4' => 'nullable|numeric|min:0',
            'price_group_5' => 'nullable|numeric|min:0',
            'old_price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'available_for_daily' => 'sometimes|boolean',
            'valid_from' => 'required|date|after_or_equal:today'
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['available_for_daily'] = $request->has('available_for_daily');

        // Remove valid_from from the bread types data as it's not a column in that table
        $validFromDate = $validated['valid_from'];
        unset($validated['valid_from']);

        // Generate code if not provided
        if (!isset($validated['code'])) {
            $lastId = BreadType::max('id') ?? 0;
            $validated['code'] = 'BT' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        }

        DB::beginTransaction();

        // Create the bread type
        $breadType = BreadType::create($validated);

        // Record the initial price in history
        BreadPriceHistory::create([
            'bread_type_id' => $breadType->id,
            'price' => $validated['price'],
            'old_price' => $validated['old_price'],
            'valid_from' => $validFromDate,
            'created_by' => auth()->id()
        ]);

        DB::commit();

        return redirect()
            ->route('bread-types.index')
            ->with('success', 'Успешно додавање на лебот');
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error creating bread type: ' . $e->getMessage());
        return back()
            ->withInput()
            ->with('error', 'Се појави грешка при додавање на лебот.');
    }
}


public function update(Request $request, BreadType $breadType)
{
    try {

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bread_types,name,' . $breadType->id,
            'code' => 'required|string|max:50|unique:bread_types,code,' . $breadType->id,
            'price' => 'required|numeric|min:0',  // Remove decimal:0,2
            'price_group_1' => 'nullable|numeric|min:0',  // Remove decimal:0,2
            'price_group_2' => 'nullable|numeric|min:0',  // Remove decimal:0,2
            'price_group_3' => 'nullable|numeric|min:0',  // Remove decimal:0,2
            'price_group_4' => 'nullable|numeric|min:0',  // Remove decimal:0,2
            'price_group_5' => 'nullable|numeric|min:0',  // Remove decimal:0,2
            'old_price' => 'required|numeric|min:0',  // Remove decimal:0,2
            'is_active' => 'sometimes|boolean',
            'available_for_daily' => 'sometimes|boolean',
            'valid_from' => 'required|date|after_or_equal:today'
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['available_for_daily'] = $request->has('available_for_daily');

        // Store the old price group values for comparison
        $oldPriceGroups = [
            'price' => $breadType->price,
            'price_group_1' => $breadType->price_group_1,
            'price_group_2' => $breadType->price_group_2,
            'price_group_3' => $breadType->price_group_3,
            'price_group_4' => $breadType->price_group_4,
            'price_group_5' => $breadType->price_group_5,
        ];

        $validated['is_active'] = $request->has('is_active');
        $validated['available_for_daily'] = $request->has('available_for_daily');

        // Check if prices have changed
        $pricesChanged = $breadType->price != $validated['price'] || 
                        $breadType->old_price != $validated['old_price'];

        DB::beginTransaction();

        if ($pricesChanged) {
            // Create a new price history record
            BreadPriceHistory::create([
                'bread_type_id' => $breadType->id,
                'price' => $validated['price'],
                'old_price' => $validated['old_price'],
                'valid_from' => $validated['valid_from'],
                'created_by' => auth()->id()
            ]);

            // Update the bread type with all fields
            $breadType->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'price' => $validated['price'],
                'price_group_1' => $validated['price_group_1'],
                'price_group_2' => $validated['price_group_2'],
                'price_group_3' => $validated['price_group_3'],
                'price_group_4' => $validated['price_group_4'],
                'price_group_5' => $validated['price_group_5'],
                'old_price' => $validated['old_price'],
                'is_active' => $validated['is_active'],
                'available_for_daily' => $validated['available_for_daily'],
            ]);
        } else {
            // Update non-price fields and group prices
            $breadType->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'price_group_1' => $validated['price_group_1'],
                'price_group_2' => $validated['price_group_2'],
                'price_group_3' => $validated['price_group_3'],
                'price_group_4' => $validated['price_group_4'],
                'price_group_5' => $validated['price_group_5'],
                'is_active' => $validated['is_active'],
                'available_for_daily' => $validated['available_for_daily']
            ]);
        }

        // Check which price groups have changed
$changedGroups = [];
foreach(['price', 'price_group_1', 'price_group_2', 'price_group_3', 'price_group_4', 'price_group_5'] as $field) {
    if(isset($validated[$field]) && isset($oldPriceGroups[$field]) && $oldPriceGroups[$field] != $validated[$field]) {
        $changedGroups[$field] = $validated[$field];
    }
}

// If any price groups changed, update company prices that use these groups
if(!empty($changedGroups)) {
    // Get all companies that have this bread type
    $companies = Company::whereHas('breadTypes', function($query) use ($breadType) {
        $query->where('bread_types.id', $breadType->id);
    })->get();
    
    foreach($companies as $company) {
        $priceGroup = $company->price_group;
        $priceGroupField = $priceGroup > 0 ? "price_group_{$priceGroup}" : "price";
        
        // Only update if this price group has changed
        if(isset($changedGroups[$priceGroupField])) {
            $newPrice = $changedGroups[$priceGroupField];
            $oldGroupPrice = $oldPriceGroups[$priceGroupField];
            
            // Get current pivot record
            $currentPivot = DB::table('bread_type_company')
                ->where('bread_type_id', $breadType->id)
                ->where('company_id', $company->id)
                ->orderBy('valid_from', 'desc')
                ->first();
            
            // We want to update the price if:
            // 1. There's no current pivot record, OR
            // 2. The current price is 0, OR
            // 3. The current price matches the old price group value (indicating it wasn't manually set)
            // We DON'T want to update if the price was manually set to a different value than the price group
            $shouldUpdate = !$currentPivot || 
                            $currentPivot->price == 0 || 
                            abs($currentPivot->price - $oldGroupPrice) < 0.01;
            
            if($shouldUpdate) {
                // Create new pivot record with updated price
                $breadType->companies()->attach($company->id, [
                    'price' => $newPrice,
                    'old_price' => $validated['old_price'],
                    'price_group' => $priceGroup,
                    'valid_from' => $validated['valid_from'],
                    'created_by' => auth()->id()
                ]);
                
             
            } else {
            
            }
        }
    }
}
        
        // // Check which price groups have changed
        // $changedGroups = [];
        // foreach(['price', 'price_group_1', 'price_group_2', 'price_group_3', 'price_group_4', 'price_group_5'] as $field) {
        //     if(isset($validated[$field]) && isset($oldPriceGroups[$field]) && $oldPriceGroups[$field] != $validated[$field]) {
        //         $changedGroups[$field] = $validated[$field];
        //     }
        // }
        
        // // If any price groups changed, update company prices that use these groups
        // if(!empty($changedGroups)) {
        //     // Get all companies that have this bread type
        //     $companies = Company::whereHas('breadTypes', function($query) use ($breadType) {
        //         $query->where('bread_type_id', $breadType->id);
        //     })->get();
            
        //     foreach($companies as $company) {
        //         $priceGroup = $company->price_group;
        //         $priceGroupField = $priceGroup > 0 ? "price_group_{$priceGroup}" : "price";
                
        //         // Only update if this price group has changed
        //         if(isset($changedGroups[$priceGroupField])) {
        //             $newPrice = $changedGroups[$priceGroupField];
                    
        //             // Get current pivot record
        //             $currentPivot = DB::table('bread_type_company')
        //                 ->where('bread_type_id', $breadType->id)
        //                 ->where('company_id', $company->id)
        //                 ->orderBy('valid_from', 'desc')
        //                 ->first();
                    
        //             // Only update if no manual override (price is same as old group price)
        //             if(!$currentPivot || $currentPivot->price == 0 || 
        //                abs($currentPivot->price - $oldPriceGroups[$priceGroupField]) < 0.01) {
                        
        //                 // Create new pivot record with updated price
        //                 $breadType->companies()->attach($company->id, [
        //                     'price' => $newPrice,
        //                     'old_price' => $validated['old_price'],
        //                     'price_group' => $priceGroup,
        //                     'valid_from' => $validated['valid_from'],
        //                     'created_by' => auth()->id()
        //                 ]);
                        
        //                 \Log::info("Updated company price from bread type update", [
        //                     'bread_type' => $breadType->name,
        //                     'company' => $company->name,
        //                     'price_group' => $priceGroup,
        //                     'old_price' => $oldPriceGroups[$priceGroupField],
        //                     'new_price' => $newPrice
        //                 ]);
        //             }
        //         }
        //     }
        // }

        DB::commit();

        return redirect()
            ->route('bread-types.index')
            ->with('success', 'Успешно ажурирање на лебот. ' . 
                ($pricesChanged ? 'Новата цена ќе важи од ' . $validated['valid_from'] : ''));
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating bread type: ' . $e->getMessage());
        return back()
            ->withInput()
            ->with('error', 'Се појави грешка при ажурирање на лебот.');
    }
}

//         // Check if prices have changed
//         $pricesChanged = $breadType->price != $validated['price'] || 
//                         $breadType->old_price != $validated['old_price'];

//         DB::beginTransaction();


//         if ($pricesChanged) {
//             // Create a new price history record
//             BreadPriceHistory::create([
//                 'bread_type_id' => $breadType->id,
//                 'price' => $validated['price'],
//                 'old_price' => $validated['old_price'],
//                 'valid_from' => $validated['valid_from'],
//                 'created_by' => auth()->id()
//             ]);

//             // Update the current prices and group prices in bread_types table
//             $breadType->update([
//                 'code' => $validated['code'],
//                 'name' => $validated['name'],
//                 'price' => $validated['price'],
//                 'price_group_1' => $validated['price_group_1'],
//                 'price_group_2' => $validated['price_group_2'],
//                 'price_group_3' => $validated['price_group_3'],
//                 'price_group_4' => $validated['price_group_4'],
//                 'price_group_5' => $validated['price_group_5'],
//                 'old_price' => $validated['old_price'],
//                 'is_active' => $validated['is_active'],
//                 'available_for_daily' => $validated['available_for_daily'],
//                 // 'last_price_change' => $validated['valid_from']
//             ]);
//         } else {
//             // Update non-price fields and group prices
//             $breadType->update([
//                 'code' => $validated['code'],
//                 'name' => $validated['name'],
//                 'price_group_1' => $validated['price_group_1'],
//                 'price_group_2' => $validated['price_group_2'],
//                 'price_group_3' => $validated['price_group_3'],
//                 'price_group_4' => $validated['price_group_4'],
//                 'price_group_5' => $validated['price_group_5'],
//                 'is_active' => $validated['is_active'],
//                 'available_for_daily' => $validated['available_for_daily']
//             ]);
//         }

//         DB::commit();

//         return redirect()
//             ->route('bread-types.index')
//             ->with('success', 'Успешно ажурирање на лебот. ' . 
//                 ($pricesChanged ? 'Новата цена ќе важи од ' . $validated['valid_from'] : ''));
//     } catch (\Exception $e) {
//         DB::rollBack();
//         Log::error('Error updating bread type: ' . $e->getMessage());
//         return back()
//             ->withInput()
//             ->with('error', 'Се појави грешка при ажурирање на лебот.');
//     }
// }

    

    public function edit(BreadType $breadType)
    {
        $priceHistory = BreadPriceHistory::where('bread_type_id', $breadType->id)
            ->orderBy('valid_from', 'desc')
            ->get();
        
        return view('bread-types.edit', compact('breadType', 'priceHistory'));
    }

    public function showCompanyPrices(BreadType $breadType)
    {
        $companies = Company::all()->map(function($company) use ($breadType) {
            // Get the latest pricing for this company and bread type
            $latestPricing = DB::table('bread_type_company')
                ->where('bread_type_id', $breadType->id)
                ->where('company_id', $company->id)
                ->orderBy('valid_from', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
                
            if ($latestPricing) {
                $company->pivot = $latestPricing;
            } else {
                // If no specific pricing exists, create default values based on company's price group
                $priceGroup = $company->price_group;
                
                // Determine price based on company's price group
                $defaultPrice = $breadType->price; // Start with default price
                
                if ($priceGroup > 0) {
                    $priceGroupField = "price_group_{$priceGroup}";
                    if (isset($breadType->$priceGroupField) && $breadType->$priceGroupField) {
                        $defaultPrice = $breadType->$priceGroupField;
                    }
                }
                
                // Create a default pivot object with the appropriate price
                $company->pivot = (object)[
                    'price' => $defaultPrice,
                    'old_price' => $breadType->old_price,
                    'price_group' => $company->price_group,
                    'valid_from' => now()->format('Y-m-d')
                ];
            }
            
          
            
            return $company;
        });
    
        return view('bread-types.company-prices', compact('breadType', 'companies'));
    }


    public function updateCompanyPrices(Request $request, BreadType $breadType, Company $company)
{
    $data = $request->validate([
        'companies.'.$company->id.'.price' => 'required|numeric|min:0',
        'companies.'.$company->id.'.old_price' => 'required|numeric|min:0',
        'companies.'.$company->id.'.price_group' => 'required|integer|min:0|max:5',
        'companies.'.$company->id.'.valid_from' => 'required|date|after_or_equal:today',
    ]);

    $companyData = $data['companies'][$company->id];
    
    // Check if price_group has changed and update the price based on price group
    $selectedPriceGroup = (int)$companyData['price_group'];
    $priceGroupField = $selectedPriceGroup > 0 ? "price_group_{$selectedPriceGroup}" : "price";
    $groupPrice = $breadType->$priceGroupField ?? $breadType->price;
    
    // If the price is different from the group price and not manually changed, use the group price
    if ($selectedPriceGroup > 0 && !$request->has('manual_price_override') && 
        abs((float)$companyData['price'] - (float)$groupPrice) < 0.01) {
        $companyData['price'] = $groupPrice;
    }
    
 
    
    // Add created_by to the data array
    $pivotData = [
        'price' => $companyData['price'],
        'old_price' => $companyData['old_price'],
        'price_group' => $companyData['price_group'],
        'valid_from' => $companyData['valid_from'],
        'created_by' => auth()->id()
    ];
    
    // Check if the relationship exists
    $existingRelation = $breadType->companies()
        ->where('company_id', $company->id)
        ->exists();

    if ($existingRelation) {
        $breadType->companies()->updateExistingPivot($company->id, $pivotData);
    } else {
        $breadType->companies()->attach($company->id, $pivotData);
    }

    // Also update the company's price_group if it changed
    if ($company->price_group != $companyData['price_group']) {
        $company->update(['price_group' => $companyData['price_group']]);
    }

    return back()->with('success', 'Цените се успешно зачувани за ' . $company->name);
}
// public function updateCompanyPrices(Request $request, BreadType $breadType, Company $company)
// {
//     $data = $request->validate([
//         'companies.'.$company->id.'.price' => 'required|numeric|min:0',
//         'companies.'.$company->id.'.old_price' => 'required|numeric|min:0',
//         'companies.'.$company->id.'.price_group' => 'required|integer|min:0|max:5',
//         'companies.'.$company->id.'.valid_from' => 'required|date|after_or_equal:today',
//     ]);

//     $companyData = $data['companies'][$company->id];
    
//     // Add created_by to the data array
//     $pivotData = [
//         'price' => $companyData['price'],
//         'old_price' => $companyData['old_price'],
//         'price_group' => $companyData['price_group'],
//         'valid_from' => $companyData['valid_from'],
//         'created_by' => auth()->id()
//     ];
    
//     // Check if the relationship exists
//     $existingRelation = $breadType->companies()
//         ->where('company_id', $company->id)
//         ->exists();

//     if ($existingRelation) {
//         $breadType->companies()->updateExistingPivot($company->id, $pivotData);
//     } else {
//         $breadType->companies()->attach($company->id, $pivotData);
//     }

//     // Also update the company's price_group if it changed
//     if ($company->price_group != $companyData['price_group']) {
//         $company->update(['price_group' => $companyData['price_group']]);
//     }

//     return back()->with('success', 'Цените се успешно зачувани за ' . $company->name);
// }



    public function destroy(BreadType $breadType)
    {
        try {
            DB::beginTransaction();
            
            // First delete related price history
            BreadPriceHistory::where('bread_type_id', $breadType->id)->delete();
            
            // Then delete the bread type itself
            $breadType->delete();
            
            DB::commit();
            
            return redirect()
                ->route('bread-types.index')
                ->with('success', 'Успешно бришење на лебот');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting bread type: ' . $e->getMessage());
            return back()->with('error', 'Се појави грешка при бришење на лебот.');
        }
    }

    // Add a new method for soft delete if you want to keep both options
    public function deactivate(BreadType $breadType)
    {
        try {
            DB::beginTransaction();
            
            $breadType->update([
                'is_active' => false,
                'deactivated_at' => now()
            ]);
            
            DB::commit();
            
            return redirect()
                ->route('bread-types.index')
                ->with('success', 'Лебот е успешно деактивиран');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deactivating bread type: ' . $e->getMessage());
            return back()->with('error', 'Се појави грешка при промена на неактивен леб.');
        }
    }

    public function getPriceAtDate(BreadType $breadType, $date)
    {
        return BreadPriceHistory::where('bread_type_id', $breadType->id)
            ->where('valid_from', '<=', $date)
            ->orderBy('valid_from', 'desc')
            ->first();
    }
}