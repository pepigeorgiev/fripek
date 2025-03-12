<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'code',
        'report_end_date',
        'mygpm_business_unit',  
        'price_group' 


    ];

    protected $casts = [
        'report_end_date' => 'date'
    ];

    protected $casts1 = [
        'type' => 'string'
    ];
    



    public function monthlyTransactions()
    {
        return $this->hasMany(MonthlySummary::class);
    }

    public function hasAnyTransactions()
    {
        return $this->dailyTransactions()->exists() 
            || $this->monthlyTransactions()->exists();
    }

    public function breadTypes()
    {
        return $this->belongsToMany(BreadType::class, 'bread_type_company')
                    ->withPivot(['price', 'old_price', 'price_group', 'valid_from', 'created_by'])
                    ->withTimestamps();
    }
    // Helper method to get the price for a specific bread type
    public function getPriceForBreadType(BreadType $breadType)
    {
        // Get company's price group (0-5)
        $priceGroup = $this->price_group;
        
        // If price group is 0, use the default price
        if ($priceGroup == 0) {
            return $breadType->price;
        }
        
        // Otherwise, use the price from the specified group
        $priceField = "price_group_{$priceGroup}";
        
        // If the price for this group is set, return it
        if ($breadType->$priceField) {
            return $breadType->$priceField;
        }
        
        // Fall back to the default price if the group price isn't set
        return $breadType->price;
    }


    protected static function booted()
{
    // This gets triggered when a company is updated
    static::updated(function ($company) {
        // Check if price_group has changed
        if ($company->isDirty('price_group')) {
            $oldPriceGroup = $company->getOriginal('price_group');
            $newPriceGroup = $company->price_group;
            
            \Log::info("Company price group changed", [
                'company' => $company->name,
                'old_price_group' => $oldPriceGroup,
                'new_price_group' => $newPriceGroup
            ]);
            
            // Get all bread types associated with this company
            $breadTypes = $company->breadTypes;
            
            // If no bread types, get all active bread types
            if ($breadTypes->isEmpty()) {
                $breadTypes = \App\Models\BreadType::where('is_active', true)->get();
            }
            
            foreach ($breadTypes as $breadType) {
                // Get the price for the new price group
                $priceFieldName = $newPriceGroup > 0 ? "price_group_{$newPriceGroup}" : "price";
                $newPrice = $breadType->$priceFieldName ?? $breadType->price;
                
                \Log::info("Updating price for bread type", [
                    'bread_type' => $breadType->name,
                    'company' => $company->name,
                    'new_price_group' => $newPriceGroup,
                    'new_price' => $newPrice
                ]);
                
                // Check if there's an existing relationship in the pivot table
                $existingRelation = $company->breadTypes()
                    ->where('bread_type_id', $breadType->id)
                    ->exists();
                    
                $pivotData = [
                    'price' => $newPrice,
                    'price_group' => $newPriceGroup,
                    'valid_from' => now()->format('Y-m-d'),
                    'created_by' => auth()->id() ?? 1 // Default to 1 if no auth user
                ];
                
                if ($existingRelation) {
                    // Update the pivot record
                    $company->breadTypes()->updateExistingPivot($breadType->id, $pivotData);
                    \Log::info("Updated existing price record", [
                        'bread_type' => $breadType->name,
                        'company' => $company->name
                    ]);
                } else {
                    // Create a new pivot record
                    $pivotData['old_price'] = $breadType->old_price;
                    $company->breadTypes()->attach($breadType->id, $pivotData);
                    \Log::info("Created new price record", [
                        'bread_type' => $breadType->name,
                        'company' => $company->name
                    ]);
                }
            }
        }
    });
}

    
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user');
    }

    public function dailyTransactions()
    {
        return $this->hasMany(DailyTransaction::class);
    }
    }
