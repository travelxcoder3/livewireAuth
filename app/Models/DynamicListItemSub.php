<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicListItemSub extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array<string>
     */
    protected $fillable = [
        'dynamic_list_item_id', // معرف البند الرئيسي
        'label',               // نص البند الفرعي
        'agency_id',
        'created_by',
    ];

    /**
     * العلاقة مع البند الرئيسي
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(DynamicListItem::class, 'dynamic_list_item_id')
            ->withDefault(); // تجنب الأخطاء عند عدم وجود بند رئيسي
    }

    /**
     * الحصول على المسار الكامل للبند الفرعي (البند الرئيسي + البند الفرعي)
     *
     * @return string
     */
    public function getFullPathAttribute(): string
    {
        return $this->item->label . ' > ' . $this->label;
    }

    public function parentItem()
{
    return $this->belongsTo(\App\Models\DynamicListItem::class, 'dynamic_list_item_id');
}



}
