<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeWallet extends Model
{
    protected $fillable = ['user_id','balance','status'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function transactions() {
        return $this->hasMany(EmployeeWalletTransaction::class, 'wallet_id');
    }
}
