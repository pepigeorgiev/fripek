<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportJob extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'file_path',
        'error',
        'completed_at'
    ];

    protected $dates = [
        'completed_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}