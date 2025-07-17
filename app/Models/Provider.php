<?php

namespace App\Models;

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
    
// في نموذج Provider (app/Models/Provider.php)
public function serviceItem()
{
    return $this->belongsTo(DynamicListItem::class, 'service_item_id');
}


}