<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DynamicListItem extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة
     */
    protected $fillable = [
        'dynamic_list_id',
        'created_by_agency',
        'label',
        'order',
    ];

    /**
     * العلاقات التي تُحمّل تلقائيًا
     */

    /**
     * تحديث القائمة عند التعديل
     */
    protected $touches = ['list'];

    /**
     * البند يتبع قائمة ديناميكية
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(DynamicList::class, 'dynamic_list_id')->withDefault();
    }

    /**
     * البند تم إنشاؤه بواسطة وكالة محددة
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'created_by_agency');
    }

    /**
     * العلاقة مع البنود الفرعية
     */
    public function subItems(): HasMany
    {
        return $this->hasMany(DynamicListItemSub::class)->orderBy('order');
    }

    /**
     * فلتر لعرض البنود التي تخص وكالة معينة فقط
     */
    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('created_by_agency', $agencyId);
    }

    /**
     * عدد البنود الفرعية المرتبطة
     */
    public function getActiveSubItemsCountAttribute(): int
    {
        return $this->subItems()->count();
    }

    /**
     * العلاقة مع جدول المبيعات (إن وُجدت)
     */
    public function sales(): HasMany
    {
        return $this->hasMany(\App\Models\Sale::class, 'service_item_id');
    }

     public function dynamicList()
    {
        return $this->belongsTo(DynamicList::class, 'dynamic_list_id');
    }
}
