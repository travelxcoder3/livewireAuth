<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['agency_id', 'name', 'email', 'phone', 'address', 'has_commission','account_type'];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
    public function sales()
    {
        return $this->hasMany(\App\Models\Sale::class);
    }
    public function collections()
    {
        return $this->hasManyThrough(
            \App\Models\Collection::class, // النموذج النهائي
            \App\Models\Sale::class,       // النموذج الوسيط
            'customer_id',                 // المفتاح في جدول Sale الذي يشير إلى Customer
            'sale_id',                     // المفتاح في جدول Collection الذي يشير إلى Sale
            'id',                          // المفتاح المحلي في جدول Customer
            'id'                           // المفتاح المحلي في جدول Sale
        );
    }

    public function images()
    {
        return $this->hasMany(CustomerImage::class);
    }

}
