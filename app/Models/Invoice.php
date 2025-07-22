<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'entity_name',
        'date',
        'user_id',
        'agency_id',
    ];
protected $with = ['sales'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'invoice_sale');
    }
}
