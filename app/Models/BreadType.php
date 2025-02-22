<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreadType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'price',
        'price_group_1',
        'price_group_2',
        'price_group_3',
        'price_group_4',
        'price_group_5',
        'old_price',
        'valid_from',
        'is_active',
        'old_bread_sold',
        'available_for_daily'
    ];

  

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'is_active' => 'boolean',
        'old_bread_sold' => 'decimal:2',
        'available_for_daily' => 'boolean',
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'bread_type_company')
            ->withPivot('price', 'old_price', 'valid_from', 'price_group', 'created_by')
            ->withCasts([
                'price' => 'decimal:2',
                'old_price' => 'decimal:2',
                'valid_from' => 'date'
            ])
            ->withTimestamps();
    }

    public function getPriceForGroup($priceGroup)
{
    if ($priceGroup === 0) {
        return $this->price;
    }
    
    $groupPrice = "price_group_" . $priceGroup;
    return $this->$groupPrice ?? $this->price;
}

    public function dailyTransactions()
    {
        return $this->hasMany(DailyTransaction::class);
    }

    public function priceHistory()
    {
        return $this->hasMany(BreadPriceHistory::class);
    }

    public function getPriceForCompany($companyId, $date = null)
    {
        $date = $date ?? now();
        
        // Try to get company-specific price
        $companyPrice = $this->companies()
            ->where('company_id', $companyId)
            ->where('valid_from', '<=', $date)
            ->orderBy('valid_from', 'desc')
            ->first();

        if ($companyPrice) {
            return [
                'price' => $companyPrice->pivot->price,
                'old_price' => $companyPrice->pivot->old_price
            ];
        }

        // Return default prices if no company-specific price exists
        return [
            'price' => $this->price,
            'old_price' => $this->old_price
        ];
    }

    // Existing scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailableForDaily($query)
    {
        return $query->where('available_for_daily', true);
    }

    public function breadSales()
    {
        return $this->hasMany(BreadSale::class);
    }
}




