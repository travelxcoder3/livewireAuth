<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalSequenceUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_sequence_id',
        'user_id',
        'step_order',
    ];

    public function sequence()
    {
        return $this->belongsTo(ApprovalSequence::class, 'approval_sequence_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
