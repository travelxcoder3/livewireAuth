<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'agency_id',
        'original_id',
        'is_system',
        'is_requested',
        'is_approved',
        'requested_by_agency',
        'request_reason',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * القائمة تتبع وكالة (في حال كانت مخصصة)
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
    /**
     * العلاقة مع البنود الرئيسية
     */
    public function items(): HasMany
    {
        return $this->hasMany(DynamicListItem::class)->orderBy('order');
    }
    /**
     * Scope لعرض القوائم المتاحة لوكالة معينة
     * يشمل القوائم التنظيمية أو الخاصة بها فقط
     */
    public function scopeVisibleToAgency($query, $agencyId)
    {
        return $query->where('is_system', true)
            ->orWhere('agency_id', $agencyId);
    }

    /**
     * اسم عرضي للقائمة حسب نوعها
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_system) {
            return "[تنظيمية] {$this->name}";
        }

        return "[وكالة {$this->agency_id}] {$this->name}";
    }

    public function requestedByAgency()
    {
        return $this->belongsTo(Agency::class, 'requested_by_agency');
    }

    /**
     * هل تم الموافقة على الطلب؟
     */
    public function isApproved()
    {
        return $this->is_approved === true;
    }

    /**
     * هل تم رفض الطلب؟
     */
    public function isRejected()
    {
        return $this->is_approved === false;
    }

    /**
     * هل لا يزال قيد الانتظار؟
     */
    public function isPending()
    {
        return is_null($this->is_approved);
    }

}
