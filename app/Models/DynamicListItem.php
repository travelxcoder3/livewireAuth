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
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array<string>
     */
    protected $fillable = [
        'dynamic_list_id', // معرف القائمة الرئيسية
        'label',           // نص البند
    ];

    /**
     * العلاقات التي يجب تحميلها تلقائياً
     *
     * @var array<string>
     */
    protected $with = ['subItems'];

    /**
     * النماذج الرئيسية التي يجب تحديثها عند الحفظ
     *
     * @var array<string>
     */
    protected $touches = ['list'];

    /**
     * العلاقة مع القائمة الرئيسية
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(DynamicList::class, 'dynamic_list_id')
            ->withDefault(); // تجنب الأخطاء عند عدم وجود قائمة رئيسية
    }

    /**
     * العلاقة مع البنود الفرعية
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subItems(): HasMany
    {
        return $this->hasMany(DynamicListItemSub::class)
            ->orderBy('created_at'); // الترتيب حسب وقت الإنشاء
    }

    /**
     * الحصول على عدد البنود الفرعية النشطة
     * (يتم حسابها بدون استخدام حقل is_active)
     *
     * @return int
     */
    public function getActiveSubItemsCountAttribute(): int
    {
        return $this->subItems()->count(); // جميع البنود تعتبر نشطة
    }
    // app/Models/DynamicListItem.php

public function dynamicList()
{
    return $this->belongsTo(\App\Models\DynamicList::class, 'dynamic_list_id');
}

}
