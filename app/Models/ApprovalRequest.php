<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $table = 'approval_requests';

    protected $fillable = [
        'model_type',
        'model_id',
        'status',
        'requested_by',
        'approval_sequence_id',
        'notes',
        'agency_id', // تمت الإضافة هنا
    ];

    // علاقة مع المستخدم الذي أنشأ الطلب
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // علاقة مع تسلسل الموافقات (اختياري)
    public function approvalSequence()
    {
        return $this->belongsTo(ApprovalSequence::class, 'approval_sequence_id');
    }

    // علاقة مع الوكالة أو الفرع الذي أنشأ الطلب
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }
} 