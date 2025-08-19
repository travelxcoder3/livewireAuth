<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['customer_id','balance','status'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
