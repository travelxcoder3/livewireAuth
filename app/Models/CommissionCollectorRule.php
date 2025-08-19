<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionCollectorRule extends Model
{
    protected $fillable = ['profile_id','method','type','value','basis'];
    public function profile() { return $this->belongsTo(CommissionProfile::class, 'profile_id'); }
}
