<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\ScopedToAgency;

class Customer extends Model
{
    use ScopedToAgency;

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
            \App\Models\Collection::class, 
            \App\Models\Sale::class,       
            'customer_id',                 
            'sale_id',                     
            'id',                          
            'id'                           
        );
    }

    public function images()
    {
        return $this->hasMany(CustomerImage::class);
    }

    public function wallet()      
    { 
        return $this->hasOne(\App\Models\Wallet::class); 
    }

    public function customer()
    { 
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function transactions()
    { 
        return $this->hasMany(\App\Models\WalletTransaction::class); 
    }


}
