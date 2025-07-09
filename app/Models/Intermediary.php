<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intermediary extends Model
{
    protected $fillable = ['agency_id', 'name'];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function contacts()
    {
        return $this->hasMany(IntermediaryContact::class);
    }
}