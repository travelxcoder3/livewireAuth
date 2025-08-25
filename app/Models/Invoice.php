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
        'invoice_number',
        'entity_name',
        'date',
        'user_id',
        'agency_id',
        'subtotal',
        'tax_total',
        'grand_total'
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
        return $this->belongsToMany(\App\Models\Sale::class, 'invoice_sale', 'invoice_id', 'sale_id')
            ->withPivot(['base_amount','tax_is_percent','tax_input','tax_amount','line_total'])
            ->withTimestamps();
    }


}
