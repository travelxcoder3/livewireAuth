<?php

namespace App\Livewire\Agency;

use App\Models\DynamicList;
use App\Models\DynamicListItem;
use App\Models\DynamicListItemSub;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Auth\Access\AuthorizationException;

#[Layout('layouts.agency')]
class DynamicLists extends Component
{
    public array $expandedLists = [];
    public array $expandedItems = [];

    public array $subItemLabel = [];
    public ?int $editingSubItemId = null;
    public string $editingSubItemLabel = '';

    public array $itemLabel = [];
    public ?int $editingItemId = null;
    public string $editingItemLabel = '';
    public ?int $agencyId;


    // لا حاجة لـ public $lists = [];

    public function mount()
    {
        $this->agencyId = Auth::user()->agency_id;
    }

    public function getListsProperty()
    {
        $agencyId = Auth::user()->agency_id;

        return DynamicList::query()
            ->where(function ($q) use ($agencyId) {
                $q->where(function ($q1) {
                    $q1->where('is_system', true)->whereNull('agency_id');
                })->orWhere('agency_id', $agencyId);
            })
            ->where(function ($q) {
                $q->where('is_requested', false)
                    ->orWhere(function ($q2) {
                        $q2->where('is_requested', true)
                            ->where('is_approved', true);
                    });
            })
            ->with([
                'items' => function ($q) use ($agencyId) {
                    $q->where(function ($subQ) use ($agencyId) {
                        $subQ->whereNull('created_by_agency')
                            ->orWhere('created_by_agency', $agencyId);
                    })
                        ->orderBy('order')
                        ->with([
                            'subItems' => function ($subQuery) use ($agencyId) {
                                $subQuery->where(function ($q) use ($agencyId) {
                                    $q->whereNull('created_by_agency')
                                        ->orWhere('created_by_agency', $agencyId);
                                })->orderBy('order');
                            }
                        ]);
                }
            ])
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.agency.dynamic-lists', [
            'lists' => $this->lists,
        ]);
    }

    public function toggleExpand($listId)
    {
        $this->expandedLists = in_array($listId, $this->expandedLists)
            ? array_diff($this->expandedLists, [$listId])
            : array_merge($this->expandedLists, [$listId]);
    }

    public function toggleItemExpand($itemId)
    {
        $this->expandedItems = in_array($itemId, $this->expandedItems)
            ? array_diff($this->expandedItems, [$itemId])
            : array_merge($this->expandedItems, [$itemId]);
    }

    public function addItem($listId)
    {
        $this->validate([
            "itemLabel.$listId" => 'required|string|max:255',
        ]);

        DynamicListItem::create([
            'dynamic_list_id' => $listId,
            'label' => $this->itemLabel[$listId],
            'order' => DynamicListItem::where('dynamic_list_id', $listId)->count() + 1,
            'created_by_agency' => Auth::user()->agency_id,
        ]);

        $this->itemLabel[$listId] = '';
        $this->dispatch('item-added');
    }
        public function startEditItem($itemId)
    {
        $item = DynamicListItem::findOrFail($itemId);

        if ($item->created_by_agency !== Auth::user()->agency_id) {
            throw new AuthorizationException("لا تملك صلاحية التعديل.");
        }

        $this->editingItemId = $item->id;
        $this->editingItemLabel = $item->label;
    }

    public function updateItem()
    {
        $this->validate([
            'editingItemLabel' => 'required|string|max:255',
        ]);

        $item = DynamicListItem::findOrFail($this->editingItemId);

        if ($item->created_by_agency !== Auth::user()->agency_id) {
            throw new AuthorizationException("لا تملك صلاحية التعديل.");
        }

        $item->update(['label' => $this->editingItemLabel]);

        $this->cancelEditItem();
        $this->dispatch('item-updated');
    }

    public function deleteItem($itemId)
    {
        $item = DynamicListItem::findOrFail($itemId);

        if ($item->created_by_agency !== Auth::user()->agency_id) {
            throw new AuthorizationException("لا تملك صلاحية الحذف.");
        }

        $item->delete();
        $this->dispatch('item-deleted');
    }

    public function cancelEditItem()
    {
        $this->editingItemId = null;
        $this->editingItemLabel = '';
    }

    public function addSubItem($itemId)
    {
        $this->validate([
            "subItemLabel.$itemId" => 'required|string|max:255',
        ]);

        $item = DynamicListItem::findOrFail($itemId);

        if ($item->created_by_agency !== Auth::user()->agency_id) {
            throw new AuthorizationException("لا تملك صلاحية الإضافة لهذا البند.");
        }

        DynamicListItemSub::create([
            'dynamic_list_item_id' => $item->id,
            'label' => $this->subItemLabel[$item->id],
            'order' => $item->subItems()->count() + 1,
            'created_by_agency' => Auth::user()->agency_id,
        ]);

        $this->subItemLabel[$item->id] = '';
        $this->dispatch('subitem-added');
    }

    public function startEditSubItem($subItemId)
    {
        $subItem = DynamicListItemSub::findOrFail($subItemId);

        if ($subItem->created_by_agency !== Auth::user()->agency_id) {
            throw new AuthorizationException("لا تملك صلاحية التعديل.");
        }

        $this->editingSubItemId = $subItem->id;
        $this->editingSubItemLabel = $subItem->label;
    }

    public function updateSubItem()
    {
        $this->validate([
            'editingSubItemLabel' => 'required|string|max:255',
        ]);

        $subItem = DynamicListItemSub::findOrFail($this->editingSubItemId);

        if ($subItem->created_by_agency !== Auth::user()->agency_id) {
            throw new AuthorizationException("لا تملك صلاحية التعديل.");
        }

        $subItem->update(['label' => $this->editingSubItemLabel]);

        $this->cancelEditSubItem();
        $this->dispatch('subitem-updated');
    }

    public function deleteSubItem($subItemId)
    {
        $subItem = DynamicListItemSub::findOrFail($subItemId);

        if ($subItem->created_by_agency !== Auth::user()->agency_id) {
            throw new AuthorizationException("لا تملك صلاحية الحذف.");
        }

        $subItem->delete();
        $this->dispatch('subitem-deleted');
    }

    public function cancelEditSubItem()
    {
        $this->editingSubItemId = null;
        $this->editingSubItemLabel = '';
    }
}
