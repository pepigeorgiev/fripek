<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LockedDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'locked_date',
        'user_id',  // null means ALL users are locked for this date
        'locked_by'
    ];

    protected $casts = [
        'locked_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
    
    /**
     * Check if a date is locked for a specific user
     * 
     * @param string $date
     * @param int $userId
     * @return bool
     */
    public static function isLocked($date, $userId)
    {
        return self::where(function($query) use ($userId) {
                // Either locked specifically for this user
                $query->where('user_id', $userId)
                    // OR locked for all users (user_id is null)
                    ->orWhereNull('user_id');
            })
            ->where('locked_date', $date)
            ->exists();
    }
    
    /**
     * Lock a date for a specific user or all users
     * 
     * @param string $date
     * @param int|null $userId (null means lock for ALL users)
     * @param int $adminId
     * @return LockedDay
     */
    public static function lockDate($date, $userId, $adminId)
    {
        // Fix: Use LockedDay::query()->create() instead of self::create()
        return LockedDay::query()->create([
            'locked_date' => $date,
            'user_id' => $userId,
            'locked_by' => $adminId
        ]);
    }
    
    /**
     * Unlock a date for a specific user or all users
     * 
     * @param string $date
     * @param int|null $userId (null means unlock for ALL users)
     * @return bool
     */
    public static function unlockDate($date, $userId = null)
    {
        $query = self::where('locked_date', $date);
        
        if ($userId !== null) {
            $query->where('user_id', $userId);
        } else {
            // If userId is null, only unlock the global lock (where user_id is null)
            $query->whereNull('user_id');
        }
        
        return $query->delete();
    }
}