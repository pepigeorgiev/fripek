<?php


namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    const ROLE_SUPER_ADMIN = 'admin-admin';
    const ROLE_ADMIN = 'admin-user';
    const ROLE_USER = 'user';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'plain_password',

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN || $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function canManageAdmins()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }



    /**
     * Companies relationship through pivot table
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
                    ->withTimestamps();
    }

    /**
     * Daily transactions relationship
     */
    public function dailyTransactions()
    {
        if ($this->isAdmin()) {
            return $this->hasMany(DailyTransaction::class);
        }
        
        return $this->hasMany(DailyTransaction::class)
            ->whereIn('company_id', $this->companies->pluck('id'));
    }

    /**
     * Check if user can access a specific company
     */
    public function canAccessCompany($companyId)
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        return $this->companies->contains($companyId);
    }

    /**
     * Get available companies for user
     */
    public function getAvailableCompanies()
    {
        if ($this->isAdmin()) {
            return Company::all();
        }
        
        return $this->companies;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Get user's primary company if exists
     */
    public function primaryCompany()
    {
        return $this->companies->first();
    }

    /**
     * Scope query to admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope query to regular users
     */
    public function scopeRegularUsers($query)
    {
        return $query->where('role', '!=', 'admin');
    }
}