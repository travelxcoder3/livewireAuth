<?php

namespace App\Models;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = ['agency_id', 'name', 'type', 'contact_info','service_item_id', 'status']; // نُبقيه مرحلياً

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'provider_id');
    }
    
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\DynamicListItem::class, 'provider_service_item', 'provider_id', 'service_item_id')
                    ->withTimestamps();
    }

    // توافق خلفي: ترجع أول خدمة إن وُجدت (لتجنّب كسر شاشات قديمة)
    public function service()
    {
        return $this->services()->limit(1);
    }

    // لا حاجة لدالتين service/serviceItem بعد الآن، ولكن نُبقيهما مؤقتاً
    public function serviceItem()
    {
        return $this->services()->limit(1);
    }

    public function getServicesLabelsAttribute(): string
    {
        return $this->services->pluck('label')->implode('، ') ?: '—';
    }

}