<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicListItemSub extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة
     */
    protected $fillable = [
        'dynamic_list_item_id',
        'created_by_agency',
        'label',
        'order',
    ];

    /**
     * العلاقة مع البند الرئيسي
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(DynamicListItem::class, 'dynamic_list_item_id')->withDefault();
    }

    /**
     * البند الفرعي مملوك لوكالة محددة
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'created_by_agency');
    }

    /**
     * علاقة العنصر الأب (Self-Relation)
     */
   

    /**
     * فلتر لإرجاع البنود الفرعية التي أنشأتها وكالة معينة
     */
    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('created_by_agency', $agencyId);
    }

    /**
     * عرض المسار الكامل للبند الفرعي (البند الرئيسي > البند الفرعي)
     */
    public function getFullPathAttribute(): string
    {
        return $this->item->label . ' > ' . $this->label;
    }

    public function parentItem()
    {
        return $this->belongsTo(DynamicListItem::class, 'dynamic_list_item_id');
    }

}
