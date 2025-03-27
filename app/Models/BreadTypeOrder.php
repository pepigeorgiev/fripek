<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreadTypeOrder extends Model
{
    protected $table = 'bread_type_order';
    protected $fillable = ['bread_type_id', 'display_order'];

    public function breadType()
    {
        return $this->belongsTo(BreadType::class);
    }
}