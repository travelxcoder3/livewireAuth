<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyPolicy extends Model
{
    protected $fillable = ['key','title','content','agency_id'];

    public function scopeKey($q, string $key){ return $q->where('key',$key); }


    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
