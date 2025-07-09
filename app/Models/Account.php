<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class Account extends Model
{
    use WithPagination;

    protected $fillable = [
        'agency_id',
        'name',
        'account_number',
        'currency',
        'balance',
        'note',
        'is_active'
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
    public function sales()
{
    return $this->hasMany(\App\Models\Sale::class);
}

}