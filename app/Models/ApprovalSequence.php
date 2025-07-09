<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'name',
        'action_type',
    ];

    // العلاقة مع الموظفين في التسلسل
    public function users()
    {
        return $this->hasMany(ApprovalSequenceUser::class)->orderBy('step_order');
    }

    // العلاقة مع الوكالة
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
