<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DynamicList extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'agency_id',
        'is_system',
        'original_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Relationship to the list items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(DynamicListItem::class)
            ->orderBy('order');
    }

    /**
     * Check if the list is editable by a given user.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function isEditableBy(User $user): bool
    {
        return $this->canEditBy($user);
    }

    /**
     * Check edit permissions for a user.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function canEditBy(User $user): bool
    {
        // Super admins can edit any list
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Non-system lists owned by user's agency
        if (!$this->is_system && $this->agency_id === $user->agency_id) {
            return true;
        }

        // Agency copies of system lists
        if ($this->original_id && $this->agency_id === $user->agency_id) {
            return true;
        }

        return false;
    }

    /**
     * Create an agency copy of the system list.
     *
     * @param int $agencyId
     * @return \App\Models\DynamicList
     * @throws \Throwable
     */
    public function createAgencyCopy(int $agencyId): DynamicList
    {
        return DB::transaction(function () use ($agencyId) {
            // Create list copy
            $copy = $this->replicate();
            $copy->is_system = false;
            $copy->agency_id = $agencyId;
            $copy->original_id = $this->id;
            $copy->save();

            // Copy all items and sub-items
            $this->items->each(function ($item) use ($copy) {
                $this->copyItemWithSubItems($item, $copy);
            });

            return $copy->load(['items.subItems']);
        });
    }

    /**
     * Copy a list item with its sub-items.
     *
     * @param \App\Models\DynamicListItem $item
     * @param \App\Models\DynamicList $targetList
     * @return void
     */
    protected function copyItemWithSubItems(DynamicListItem $item, DynamicList $targetList): void
    {
        $itemCopy = $item->replicate();
        $itemCopy->dynamic_list_id = $targetList->id;
        $itemCopy->save();

        $item->subItems->each(function ($subItem) use ($itemCopy) {
            $subItemCopy = $subItem->replicate();
            $subItemCopy->dynamic_list_item_id = $itemCopy->id;
            $subItemCopy->save();
        });
    }

    /**
     * Scope for system lists.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for agency lists.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $agencyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAgency($query, ?int $agencyId = null)
    {
        return $query->where('is_system', false)
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));
    }

    /**
     * Get the full name including agency info (for display purposes).
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_system) {
            return "[System] {$this->name}";
        }

        return "[Agency {$this->agency_id}] {$this->name}";
    }
}
