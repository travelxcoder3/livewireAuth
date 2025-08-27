<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionCollectorMonthly extends Model
{
    protected $fillable = [
        'agency_id','year','month','method','type','value','basis','locked'
    ];
    protected $casts = [
        'locked'=>'bool','year'=>'int','month'=>'int','method'=>'int'
    ];
}
