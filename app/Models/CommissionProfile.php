<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionProfile extends Model
{
    protected $fillable = [
        'agency_id','name','is_active','employee_rate','days_to_debt','debt_behavior'
    ];

    public function agency() { return $this->belongsTo(Agency::class); }
    public function collectorRules() { return $this->hasMany(CommissionCollectorRule::class, 'profile_id'); }
    public function employeeRateOverrides() { return $this->hasMany(CommissionEmployeeRateOverride::class, 'profile_id'); }
    public function collectorOverrides() { return $this->hasMany(CommissionCollectorOverride::class, 'profile_id'); }
}
