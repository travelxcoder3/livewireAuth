<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntermediaryContact extends Model
{
    protected $fillable = ['intermediary_id', 'type', 'value'];

    public function intermediary()
    {
        return $this->belongsTo(Intermediary::class);
    }
}