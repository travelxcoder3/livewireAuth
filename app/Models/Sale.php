<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

        protected $casts = [
            'sale_date' => 'date',
        ];
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
        return $this->belongsTo(Customer::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
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
        return $this->belongsTo(\App\Models\DynamicListItem::class, 'service_item_id');
    }
    
    public function items()
    {
        return $this->hasMany(SaleItem::class); // أو الاسم الصحيح
    }


}