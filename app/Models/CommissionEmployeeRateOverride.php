<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionEmployeeRateOverride extends Model
{
    protected $fillable = ['profile_id','user_id','rate'];
    public function profile() { return $this->belongsTo(CommissionProfile::class, 'profile_id'); }
    public function employee() { return $this->belongsTo(User::class, 'user_id'); }
}
