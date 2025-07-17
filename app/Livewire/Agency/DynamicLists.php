<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\DynamicList;
use App\Models\DynamicListItem;
use App\Models\DynamicListItemSub;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

#[Layout('layouts.agency')]
class DynamicLists extends Component
{
    // الخصائص العامة
    public string $newListName = ''; // اسم القائمة الجديدة
    public array $itemLabel = []; // تسميات البنود الرئيسية
    public array $subItemLabel = []; // تسميات البنود الفرعية
    public array $expandedLists = []; // القوائم المفتوحة/الموسعة

    // خصائص تعديل البنود الفرعية
    public ?int $editingSubItemId = null; // معرف البند الفرعي قيد التعديل
    public string $editingSubItemLabel = ''; // نص البند الفرعي قيد التعديل

    // خصائص تعديل البنود الرئيسية
    public ?int $editingItemId = null;
    public string $editingItemLabel = '';

    // حالات خاصة بالنسخ
    public bool $isCloning = false; // حالة النسخ الحالية
    public array $clonedListsCache = []; // تخزين مؤقت للقوائم المنسوخة

    /**
     * الحصول على القوائم المتاحة للوكالة
     * @return Collection
     */
    public function getListsProperty(): Collection
    {
        $user = Auth::user();

        // إذا كان المستخدم مدير نظام، أحصل على القوائم النظامية فقط
        if ($user->hasRole('super-admin')) {
            return DynamicList::with(['items.subItems'])
                ->where('is_system', true)
                ->orderBy('id')
                ->get();
        }

        // القوائم الخاصة بالوكالة
        $agencyLists = DynamicList::with(['items.subItems'])
            ->where('agency_id', $user->agency_id)
            ->get();

        // القوائم النظامية التي لم يتم نسخها بعد
        $copiedOriginalIds = $agencyLists->whereNotNull('original_id')
            ->pluck('original_id')
            ->toArray();

        $systemLists = DynamicList::with(['items.subItems'])
            ->where('is_system', true)
            ->whereNotIn('id', $copiedOriginalIds)
            ->get();

        // دمج القوائم وترتيبها حسب المعرف
        return $agencyLists->merge($systemLists)->sortBy('id');
    }

    /**
     * تبديل حالة توسيع/طي القائمة
     * @param int $listId
     */
    public function toggleExpand($listId)
    {
        if (in_array($listId, $this->expandedLists)) {
            $this->expandedLists = array_diff($this->expandedLists, [$listId]);
        } else {
            $this->expandedLists[] = $listId;
        }
    }

    // ============ إدارة البنود الفرعية ============

    /**
     * إضافة بند فرعي جديد
     * @param int $itemId
     * @throws AuthorizationException
     */
    public function addSubItem($itemId)
    {
        $this->validate([
            "subItemLabel.$itemId" => 'required|string|max:255',
        ]);

        $item = DynamicListItem::with(['list', 'subItems'])->findOrFail($itemId);
        $user = Auth::user();

        // التحقق من الصلاحيات وحالة القائمة النظامية
        if (!$item->list->isEditableBy($user)) {
            if ($item->list->is_system) {
                $this->handleSystemListSubItem($item);
                return;
            }
            throw new AuthorizationException("لا تملك صلاحية التعديل.");
        }

        // إضافة البند الفرعي
        $this->createSubItem($item);
    }

    /**
     * معالجة إضافة بند فرعي للقوائم النظامية
     * @param DynamicListItem $item
     */
    protected function handleSystemListSubItem(DynamicListItem $item)
    {
        $user = Auth::user();
        $clonedList = $this->getOrCreateClonedList($item->list, $user->agency_id);

        $newItem = $clonedList->items()
            ->where('label', $item->label)
            ->first();

        if ($newItem) {
            $this->createSubItem($newItem);
            $this->expandedLists = array_unique([...$this->expandedLists, $clonedList->id]);
            $this->dispatch('subitem-added', listId: $clonedList->id, itemId: $newItem->id);
        }
    }

    /**
     * إنشاء بند فرعي جديد
     * @param DynamicListItem $item
     */
    protected function createSubItem(DynamicListItem $item)
    {
        $newSubItem = $item->subItems()->create([
            'label' => $this->subItemLabel[$item->id],
            'dynamic_list_item_id' => $item->id
        ]);

        if (!$item->relationLoaded('subItems')) {
            $item->setRelation('subItems', collect());
        }
        $item->subItems->push($newSubItem);

        $this->subItemLabel[$item->id] = '';
        $this->dispatch('subitem-added', listId: $item->list->id, itemId: $item->id);
    }

    /**
     * الحصول على القائمة المنسوخة أو إنشائها إذا لم تكن موجودة
     * @param DynamicList $systemList
     * @param int $agencyId
     * @return DynamicList
     */
    protected function getOrCreateClonedList(DynamicList $systemList, $agencyId): DynamicList
    {
        return $this->clonedListsCache[$systemList->id] ??= DynamicList::with(['items.subItems'])
            ->where('original_id', $systemList->id)
            ->where('agency_id', $agencyId)
            ->firstOr(function () use ($systemList, $agencyId) {
                return $systemList->createAgencyCopy($agencyId)
                    ->load(['items.subItems']);
            });
    }

    /**
     * إضافة بند رئيسي جديد
     * @param int $listId
     * @throws AuthorizationException
     */
    public function addItem($listId)
    {
        $this->validate([
            "itemLabel.$listId" => 'required|string|max:255',
        ]);

        $list = DynamicList::findOrFail($listId);
        $user = Auth::user();

        \Log::info('DynamicLists/addItem user:', ['id' => $user->id, 'role' => $user->getRoleNames(), 'agency_id' => $user->agency_id]);
        \Log::info('DynamicLists/addItem list:', ['id' => $list->id, 'agency_id' => $list->agency_id, 'is_system' => $list->is_system, 'original_id' => $list->original_id]);

        // إذا كانت القائمة نظامية وليست منسوخة للوكالة، أنشئ نسخة للوكالة
        if ($list->is_system && ($list->agency_id === null || $list->agency_id != $user->agency_id)) {
            $clonedList = $this->getOrCreateClonedList($list, $user->agency_id);
            $list = $clonedList;
        }

        // التحقق من الصلاحيات
        if (!$this->canEditList($list)) {
            throw new AuthorizationException("لا تملك صلاحية التعديل على هذه القائمة.");
        }

        // حماية قبل الاستخدام

        $order = $list->items()->max('order') + 1;

        $list->items()->create([
            'label' => $this->itemLabel[$list->id],
            'order' => $order,
        ]);

        unset($this->itemLabel[$list->id]);
        $this->dispatch('item-added', listId: $list->id);
    }

    /**
     * بدء تعديل البند الفرعي
     * @param int $subItemId
     * @throws AuthorizationException
     */
    public function startEditSubItem($subItemId)
    {
        $subItem = DynamicListItemSub::findOrFail($subItemId);

        if (!$this->canEditList($subItem->item->list)) {
            throw new AuthorizationException("لا تملك صلاحية التعديل.");
        }

        $this->editingSubItemId = $subItem->id;
        $this->editingSubItemLabel = $subItem->label;
    }

    /**
     * تحديث البند الفرعي
     * @throws AuthorizationException
     */
    public function updateSubItem()
    {
        $this->validate([
            'editingSubItemLabel' => 'required|string|max:255',
        ]);

        $subItem = DynamicListItemSub::findOrFail($this->editingSubItemId);

        if (!$this->canEditList($subItem->item->list)) {
            throw new AuthorizationException("لا تملك صلاحية التعديل.");
        }

        $subItem->update(['label' => $this->editingSubItemLabel]);

        $this->cancelEditSubItem();
        $this->dispatch('subitem-updated');
    }

    /**
     * حذف البند الفرعي
     * @param int $subItemId
     * @throws AuthorizationException
     */
    public function deleteSubItem($subItemId)
    {
        $subItem = DynamicListItemSub::with('item.list')->findOrFail($subItemId);

        if (!$this->canEditList($subItem->item->list)) {
            throw new AuthorizationException("لا تملك صلاحية الحذف.");
        }

        $subItem->delete();
        $this->dispatch('subitem-deleted');
    }

    /**
     * إلغاء تعديل البند الفرعي
     */
    public function cancelEditSubItem()
    {
        $this->editingSubItemId = null;
        $this->editingSubItemLabel = '';
    }

    /**
     * بدء تعديل البند الرئيسي
     * @param int $itemId
     */
    public function startEditItem($itemId)
    {
        $item = DynamicListItem::findOrFail($itemId);
        $this->editingItemId = $item->id;
        $this->editingItemLabel = $item->label;
    }

    /**
     * تحديث البند الرئيسي
     * @throws AuthorizationException
     */
    public function updateItem()
    {
        $this->validate([
            'editingItemLabel' => 'required|string|max:255',
        ]);

        $item = DynamicListItem::findOrFail($this->editingItemId);

        if (!$this->canEditList($item->list)) {
            throw new AuthorizationException("لا تملك صلاحية التعديل.");
        }

        $item->update(['label' => $this->editingItemLabel]);

        $this->editingItemId = null;
        $this->editingItemLabel = '';
        $this->dispatch('item-updated');
    }

    /**
     * حذف البند الرئيسي
     * @param int $itemId
     * @throws AuthorizationException
     */
    public function deleteItem($itemId)
    {
        $item = DynamicListItem::findOrFail($itemId);

        if (!$this->canEditList($item->list)) {
            throw new AuthorizationException("لا تملك صلاحية الحذف.");
        }

        // حذف البنود الفرعية أولاً
        $item->subItems()->delete();
        $item->delete();

        $this->dispatch('item-deleted');
    }

    /**
     * عرض المكون
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.agency.dynamic-lists', [
            'lists' => $this->lists,
        ]);
    }

    public function canEditList($list)
    {
        return $list->isEditableBy(auth()->user());
    }

}
