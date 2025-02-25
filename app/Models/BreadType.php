<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BreadType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'code', 
        'price', 
        'price_group_1',
        'price_group_2',
        'price_group_3',
        'price_group_4',
        'price_group_5',
        'old_price', 
        'is_active',
        'available_for_daily',
        'valid_from',
        'last_price_change',
        'deactivated_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'available_for_daily' => 'boolean',
        'valid_from' => 'date',
        'last_price_change' => 'date',
        'deactivated_at' => 'datetime'
    ];

    // Relationships
    public function companies()
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['price', 'old_price', 'price_group', 'valid_from', 'created_by'])
            ->withTimestamps();
    }

    public function priceHistory()
    {
        return $this->hasMany(BreadPriceHistory::class);
    }

    public function transactions()
    {
        return $this->hasMany(DailyTransaction::class, 'bread_type_id');
    }
    
    /**
     * Get the current price for this bread type for a specific company
     * This method uses company's price group to determine the price
     */

     public function getPriceForCompanyWithCache($companyId, $date)
     {
         $cacheKey = "price_company_{$companyId}_bread_{$this->id}_date_" . date('Y-m-d', strtotime($date));
         
         return Cache::remember($cacheKey, 60 * 24, function() use ($companyId, $date) {
             return $this->getPriceForCompany($companyId, $date);
         });
     }

    public function getPriceForCompany($companyId, $date = null)
    {
        $date = $date ?: now()->format('Y-m-d');
        
        // Get the company
        $company = Company::find($companyId);
        if (!$company) {
            Log::warning("Company not found for ID: {$companyId}");
            return [
                'price' => $this->price,
                'old_price' => $this->old_price
            ];
        }
        
        // Try to get company-specific price from pivot
        $specificPrice = DB::table('bread_type_company')
            ->where('bread_type_id', $this->id)
            ->where('company_id', $companyId)
            ->where('valid_from', '<=', $date)
            ->orderBy('valid_from', 'desc')
            ->first();
            
        if ($specificPrice) {
            Log::debug("Found specific price for company {$companyId}", [
                'price' => $specificPrice->price,
                'old_price' => $specificPrice->old_price
            ]);
            
            return [
                'price' => $specificPrice->price,
                'old_price' => $specificPrice->old_price
            ];
        }
        
        // If no specific price, use the company's price group
        $priceGroup = $company->price_group;
        $price = $this->price; // Default to regular price
        
        if ($priceGroup > 0) {
            $priceField = "price_group_{$priceGroup}";
            if (isset($this->$priceField) && $this->$priceField) {
                Log::debug("Using price group {$priceGroup} for company {$companyId}", [
                    'price' => $this->$priceField
                ]);
                $price = $this->$priceField;
            }
        }
        
        return [
            'price' => $price,
            'old_price' => $this->old_price
        ];
    }
    
    /**
     * Legacy method for backward compatibility
     */
    public function getCurrentPrice($companyId, $date = null)
    {
        return $this->getPriceForCompany($companyId, $date)['price'];
    }
    public function dailyTransactions()
{
    return $this->hasMany(DailyTransaction::class, 'bread_type_id');
}
}