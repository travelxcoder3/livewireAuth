<?php

namespace App\Models;
use App\Models\Sale;


use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = ['agency_id', 'name', 'type', 'contact_info','service_item_id', 'status'];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
    public function service()
    {
        return $this->belongsTo(\App\Models\DynamicListItem::class, 'service_item_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'provider_id');
    }
    
    public function serviceItem()
    {
        return $this->belongsTo(DynamicListItem::class, 'service_item_id');
    }


}