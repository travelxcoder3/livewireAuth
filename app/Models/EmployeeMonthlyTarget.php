<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeMonthlyTarget extends Model
{
    protected $fillable = [
        'agency_id','user_id','year','month','main_target','sales_target','override_rate','locked','created_by','updated_by'
    ];
}