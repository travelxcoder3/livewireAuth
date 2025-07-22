<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Obligation extends Model
{
    protected $fillable = ['agency_id', 'title', 'description', 'start_date', 'end_date', 'for_all'];

       protected $casts = [
        'start_date' => 'date',    
        'end_date'   => 'date',
        'for_all'    => 'boolean',
    ];
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_obligation');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}

