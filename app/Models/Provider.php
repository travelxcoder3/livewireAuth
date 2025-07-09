<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = ['agency_id', 'name', 'type', 'contact_info'];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}