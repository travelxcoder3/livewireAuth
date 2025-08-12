<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = ['quotation_id','service_name','description','route','service_date','conditions','price','position'];
    public function quotation(){ return $this->belongsTo(Quotation::class); }
}


    