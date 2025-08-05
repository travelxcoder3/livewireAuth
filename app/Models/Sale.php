<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Sale extends Model
{
    protected $fillable = [
        'agency_id',
        'user_id',
        'customer_id',
        'service_type_id',
        'provider_id',
        'intermediary_id',
        'account_id',
        'sale_date',
        'beneficiary_name',
        'route',
        'pnr',
        'reference',
        'status',
        'payment_method',
        'payment_type',
        'receipt_number',
        'phone_number',
        'commission',
        'usd_buy',
        'usd_sell',
        'amount_paid',
        'depositor_name',
        'sale_profit',
        'customer_via',
        'service_date',
        'expected_payment_date',
        'sale_group_id',
        'updated_by',
        'duplicated_by'
    ];

        protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->sale_group_id)) {
                $sale->sale_group_id = Str::uuid();
            }
        });
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(\App\Models\DynamicListItem::class, 'service_type_id');
    }



    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function intermediary()
    {
        return $this->belongsTo(Intermediary::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function collections()
    {
        return $this->hasMany(\App\Models\Collection::class);
    }

    public function service()
    {
        return $this->belongsTo(\App\Models\DynamicListItem::class, 'service_type_id');
    }
    
    public function items()
    {
        return $this->hasMany(SaleItem::class); // أو الاسم الصحيح
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_sale');
    }

        public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function duplicatedBy()
{
    return $this->belongsTo(User::class, 'duplicated_by');
}



}