<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'user_name',
        'phone',
        'email',
        'password',
        'agency_id',
        'is_active',
        'department_id',
        'position_id',
        'must_change_password',
        'sales_target',
        'last_activity_at',
        'main_target'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\DynamicListItem::class, 'department_id');
    }

    public function position()
    {
        return $this->belongsTo(\App\Models\DynamicListItem::class, 'position_id');
    }

   public function obligations()
{
    return $this->belongsToMany(Obligation::class, 'user_obligation');
}

    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }
    
    public function employeeWallet() {
        return $this->hasOne(\App\Models\EmployeeWallet::class);
    }
    public function employeeWalletTransactions() {
        return $this->hasManyThrough(
            \App\Models\EmployeeWalletTransaction::class,
            \App\Models\EmployeeWallet::class,
            'user_id', 'wallet_id', 'id', 'id'
        );
    }

}
