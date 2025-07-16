<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['agency_id', 'name', 'email', 'phone', 'address' ,'has_commission'];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}