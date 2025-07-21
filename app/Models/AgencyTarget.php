<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyTarget extends Model
{

     protected $fillable = [
        'agency_id',
        'month',
        'target_amount',
    ];
   // داخل موديل AgencyTarget
    public function agency()
    {
        return $this->belongsTo(\App\Models\Agency::class);
    }

}
