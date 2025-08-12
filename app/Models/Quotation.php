<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'agency_id','user_id','quotation_number','quotation_date','to_client',
        'tax_name','tax_rate','currency','lang','notes','subtotal','tax_amount','grand_total',
    ];





    public function items(){ return $this->hasMany(QuotationItem::class); }
    public function terms(){ return $this->hasMany(QuotationTerm::class); }
    public function agency(){ return $this->belongsTo(Agency::class); }
    public function user(){ return $this->belongsTo(User::class); }


       protected static function booted()
    {
        static::created(function (Quotation $q) {
            if (empty($q->quotation_number)) {
                $q->quotation_number = 'ATHKA-Q-' . str_pad((string)$q->id, 4, '0', STR_PAD_LEFT);
                $q->saveQuietly();
            }
        });
    }

}
