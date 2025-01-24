<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyActivity extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'activity_type', 'description'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}