<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;   // ← أضِف هذا السطر

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

    // داخل class Invoice
    protected function entityName(): Attribute
    {
        return Attribute::get(function ($value) {
            $firstSale = $this->sales->first();

            // فاتورة فردية ⇒ استخدم المستفيد أولاً بغضّ النظر عمّا هو مخزَّن
            if ($this->sales->count() === 1 && $firstSale) {
                $beneficiary = trim((string)($firstSale->beneficiary_name ?? ''));
                if ($beneficiary !== '') {
                    return $beneficiary;
                }
                // إن لم يوجد مستفيد نرجع للاسم المخزَّن أو اسم العميل
                return $value ?: (optional($firstSale->customer)->name ?? null);
            }

            // فواتير مجمّعة تبقى كما هي
            return $value;
        });
    }

}
