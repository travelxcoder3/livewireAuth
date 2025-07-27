<?php

namespace App\Livewire\Admin;

use App\Models\DynamicList;
use App\Models\DynamicListItem;
use App\Models\DynamicListItemSub;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;


#[Layout('layouts.admin')]
class DynamicLists extends Component
{
    // State Properties
    public EloquentCollection $lists;
    public array $expandedLists = [];
    public array $expandedItems = [];

    // List Management
    public string $newListName = '';
    public ?int $editingListId = null;

    // Item Management
    public array $itemLabel = [];
    public ?int $editingItemId = null;
    public string $editingItemLabel = '';

    // Sub-Item Management
    public array $subItemLabel = [];
    public ?int $editingSubItemId = null;
    public string $editingSubItemLabel = '';

    /**
     * Initialize component
     */
    public function mount(): void
    {
        $this->loadLists();
    }

    /**
     * Load lists with their items and sub-items
     */
    public function loadLists(): void
    {
        $user = Auth::user();
        $agencyId = $user->agency_id;

        $query = DynamicList::query()
            ->with([
                'items' => fn($q) => $q->where('created_by_agency', $agencyId)->with([
                    'subItems' => fn($sq) => $sq->where('created_by_agency', $agencyId),
                ]),
            ])
            ->where(function ($q) use ($agencyId) {
                $q->where('is_system', true)
                    ->orWhere('agency_id', $agencyId);
            })
            ->orderBy('name');

        $this->lists = $query->get();
    }


    // ==================== UI State Management ====================

    /**
     * Toggle list expansion state
     */
    public function toggleExpand(int $listId): void
    {
        if (in_array($listId, $this->expandedLists)) {
            $this->expandedLists = array_diff($this->expandedLists, [$listId]);
            return;
        }

        $this->expandedLists[] = $listId;
    }

    /**
     * Toggle item expansion state
     */
    public function toggleItemExpand(int $itemId): void
    {
        if (in_array($itemId, $this->expandedItems)) {
            $this->expandedItems = array_diff($this->expandedItems, [$itemId]);
            return;
        }

        $this->expandedItems[] = $itemId;
    }

    // ==================== Authorization Checks ====================

    /**
     * Check if list can be edited
     */
    public function canEditList(DynamicList $list): bool
    {
        return !$list->is_system;
    }

    /**
     * Check if list can be deleted
     */
    public function canDeleteList(DynamicList $list): bool
    {
        return !$list->is_system;
    }

    /**
     * Check if item can be edited
     */
    public function canEditItem(DynamicListItem $item): bool
    {
        return $item->created_by_agency === Auth::user()->agency_id;
    }


    /**
     * Check if sub-item can be edited
     */
    public function canEditSub(DynamicListItemSub $sub): bool
    {
        return $sub->created_by_agency === Auth::user()->agency_id;
    }

    // ==================== List Management ====================

    /**
     * Create new list
     */
    public function saveList(): void
    {
        $this->validate([
            'newListName' => 'required|string|max:255|unique:dynamic_lists,name',
        ]);

        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super-admin');
        DynamicList::create([
            'name' => $this->newListName,
            'agency_id' => $isSuperAdmin ? null : $user->agency_id,
            'is_system' => $isSuperAdmin,
        ]);

        $this->reset('newListName');
        $this->dispatch('list-created');
        $this->loadLists();
    }

    /**
     * Start editing list
     */
    public function editList(int $listId): void
    {
        $list = DynamicList::findOrFail($listId);
        $this->editingListId = $listId;
        $this->newListName = $list->name;
    }

    /**
     * Update list
     */
    public function updateList(): void
    {
        $this->validate([
            'newListName' => 'required|string|min:2|max:255',
        ]);

        $list = DynamicList::findOrFail($this->editingListId);

        if (!$this->canEditList($list)) {
            throw new AuthorizationException(__('You are not authorized to edit this list.'));
        }

        $list->update(['name' => $this->newListName]);
        $this->cancelEditList();
        $this->dispatch('list-updated');
        $this->loadLists();
    }

    /**
     * Cancel list editing
     */
    public function cancelEditList(): void
    {
        $this->editingListId = null;
        $this->newListName = '';
    }

    /**
     * Delete list
     */
    public function deleteList(int $listId): void
    {
        $list = DynamicList::with('items.subItems')->findOrFail($listId);

        if (!$this->canDeleteList($list)) {
            throw new AuthorizationException(__('You are not authorized to delete this list.'));
        }

        $list->items->each(function ($item) {
            $item->subItems()->delete();
            $item->delete();
        });

        $list->delete();
        $this->dispatch('list-deleted');
        $this->loadLists();
    }

    // ==================== Item Management ====================

    /**
     * Add new item
     */
    public function addItem(int $listId): void
    {
        $this->validate([
            "itemLabel.$listId" => 'required|string|max:255',
        ], [], [
            "itemLabel.$listId" => __('Item name'),
        ]);

        DynamicListItem::create([
            'dynamic_list_id' => $listId,
            'label' => $this->itemLabel[$listId],
            'order' => DynamicListItem::where('dynamic_list_id', $listId)->max('order') + 1,
            'created_by_agency' => Auth::user()->agency_id,
        ]);

        unset($this->itemLabel[$listId]);
        $this->dispatch('item-created');
        $this->loadLists();
    }


    /**
     * Start editing item
     */
    public function startEditItem(int $itemId): void
    {
        $item = DynamicListItem::findOrFail($itemId);

        if (!$this->canEditItem($item)) {
            throw new AuthorizationException(__('You are not authorized to edit this item.'));
        }

        $this->editingItemId = $itemId;
        $this->editingItemLabel = $item->label;
    }

    /**
     * Update item
     */
    public function updateItem(): void
    {
        $this->validate([
            'editingItemLabel' => 'required|string|max:255',
        ], [], [
            'editingItemLabel' => __('Item name'),
        ]);

        $item = DynamicListItem::findOrFail($this->editingItemId);

        if (!$this->canEditItem($item)) {
            throw new AuthorizationException(__('You are not authorized to edit this item.'));
        }

        $item->update(['label' => $this->editingItemLabel]);
        $this->cancelEditItem();
        $this->dispatch('item-updated');
        $this->loadLists();
    }

    /**
     * Cancel item editing
     */
    public function cancelEditItem(): void
    {
        $this->editingItemId = null;
        $this->editingItemLabel = '';
    }

    /**
     * Delete item
     */
    public function deleteItem(int $itemId): void
    {
        $item = DynamicListItem::findOrFail($itemId);

        if (!$this->canEditItem($item)) {
            throw new AuthorizationException(__('You are not authorized to delete this item.'));
        }

        $item->subItems()->delete();
        $item->delete();
        $this->dispatch('item-deleted');
        $this->loadLists();
    }

    // ==================== Sub-Item Management ====================

    /**
     * Add new sub-item
     */
    public function addSubItem(int $itemId): void
    {
        $this->validate([
            "subItemLabel.$itemId" => 'required|string|min:2|max:100',
        ]);

        DynamicListItemSub::create([
            'dynamic_list_item_id' => $itemId,
            'label' => $this->subItemLabel[$itemId],
            'order' => DynamicListItemSub::where('dynamic_list_item_id', $itemId)->count() + 1,
            'created_by_agency' => Auth::user()->agency_id,
        ]);

        unset($this->subItemLabel[$itemId]);
        $this->dispatch('subitem-created');
        $this->loadLists();
    }


    /**
     * Start editing sub-item
     */
    public function startEditSubItem(int $subItemId): void
    {
        $subItem = DynamicListItemSub::findOrFail($subItemId);

        if (!$this->canEditSub($subItem)) {
            throw new AuthorizationException(__('You are not authorized to edit this sub-item.'));
        }

        $this->editingSubItemId = $subItem->id;
        $this->editingSubItemLabel = $subItem->label;
    }

    /**
     * Update sub-item
     */
    public function updateSubItem(): void
    {
        $this->validate([
            'editingSubItemLabel' => 'required|string|min:2|max:100',
        ]);

        $subItem = DynamicListItemSub::findOrFail($this->editingSubItemId);

        if (!$this->canEditSub($subItem)) {
            throw new AuthorizationException(__('You are not authorized to edit this sub-item.'));
        }

        $subItem->update(['label' => $this->editingSubItemLabel]);
        $this->cancelEditSubItem();
        $this->dispatch('subitem-updated');
        $this->loadLists();
    }

    /**
     * Cancel sub-item editing
     */
    public function cancelEditSubItem(): void
    {
        $this->editingSubItemId = null;
        $this->editingSubItemLabel = '';
    }

    /**
     * Delete sub-item
     */
    public function deleteSubItem(int $subItemId): void
    {
        $subItem = DynamicListItemSub::findOrFail($subItemId);

        if (!$this->canEditSub($subItem)) {
            throw new AuthorizationException(__('You are not authorized to delete this sub-item.'));
        }

        $subItem->delete();
        $this->dispatch('subitem-deleted');
        $this->loadLists();
    }
    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.admin.dynamic-lists')
            ->with([
                'lists' => $this->lists,
            ]);
    }
}
