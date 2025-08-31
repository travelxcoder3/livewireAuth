<?php

// app/Models/Collection.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
   

    protected $fillable = [
    'agency_id','sale_id','amount','payment_date','method','note','user_id',
    'collector_user_id','collector_method' 
    ];

    public function collector()
    {
        return $this->belongsTo(User::class,'collector_user_id');
    }


    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function customerType()
    {
        return $this->belongsTo(DynamicListItemSub::class, 'customer_type_id');
    }

    public function debtType()
    {
        return $this->belongsTo(DynamicListItemSub::class, 'debt_type_id');
    }

    public function customerResponse()
    {
        return $this->belongsTo(DynamicListItemSub::class, 'customer_response_id');
    }

    public function customerRelation()
    {
        return $this->belongsTo(DynamicListItemSub::class, 'customer_relation_id');
    }

    public function delayReason()
    {
        return $this->belongsTo(DynamicListItemSub::class, 'delay_reason_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function getCleanNoteAttribute(): string
    {
        $raw = trim((string) $this->note);
        if ($raw === '') return '';

        if (preg_match('/سداد\s+محفظة\s+لمجموعة\s*#[-0-9a-fA-F]{36}\s*\(سجل\s*#\d+\)/u', $raw)) return '';
        if (preg_match('/^سداد\s+محفظة/u', $raw)) return '';

        return $raw;
    }


}
