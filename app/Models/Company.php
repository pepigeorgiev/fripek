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
        return $this->belongsToMany(BreadType::class)
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


    
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user');
    }

    public function dailyTransactions()
    {
        return $this->hasMany(DailyTransaction::class);
    }
    }
