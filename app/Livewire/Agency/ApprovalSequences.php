<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\ApprovalSequence;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.agency')]
class ApprovalSequences extends Component
{
    use WithPagination;

    public bool $showAddModal = false;
    public string $name = '';
    public string $action_type = '';
    public array $approvers = [''];
    public $actionTypes = [
        'manage_provider' => 'طلب إنشاء مزود',
        'manage_account' => 'طلب إنشاء حساب',
        'manage_service' => 'طلب إنشاء خدمة',
        'sale_edit' => 'طلب تعديل عملية بيع',
    ];
    

    public bool $showEditModal = false;
    public ?int $editingSequenceId = null;
    public string $editName = '';
    public string $editActionType = '';
    public array $editApprovers = [''];

    public bool $showViewModal = false;
    public int|null $viewSequenceId = null;
    public string $viewName = '';
    public string $viewActionType = '';
    public array $viewApprovers = [];

    public function viewSequence($id)
    {
        $sequence = ApprovalSequence::with('users.user')->findOrFail($id);

        $this->viewSequenceId = $sequence->id;
        $this->viewName = $sequence->name;
        $this->viewActionType = $sequence->action_type;

        $this->viewApprovers = $sequence->users
            ->sortBy('step_order')
            ->pluck('user.name')
            ->filter()
            ->values()
            ->toArray();

        $this->showViewModal = true;
        $this->dispatch('viewModalOpened');

    }

    public function editSequence($id)
    {
        $sequence = ApprovalSequence::with('users')->findOrFail($id);

        $this->editingSequenceId = $sequence->id;
        $this->editName = $sequence->name;
        $this->editActionType = $sequence->action_type;
        $this->editApprovers = $sequence->users->sortBy('step_order')->pluck('user_id')->toArray();

        $this->showEditModal = true;
        $this->dispatch('openEditModal');
    }

    public function updateSequence()
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editActionType' => 'required|string|max:255',
            'editApprovers' => 'required|array|min:1',
            'editApprovers.*' => 'required|exists:users,id',
        ]);

        $sequence = ApprovalSequence::findOrFail($this->editingSequenceId);
        $sequence->update([
            'name' => $this->editName,
            'action_type' => $this->editActionType,
        ]);

        $sequence->users()->delete();

        foreach ($this->editApprovers as $index => $userId) {
            $sequence->users()->create([
                'user_id' => $userId,
                'step_order' => $index + 1,
            ]);
        }

        $this->showEditModal = false;
        $this->dispatch('sequenceSaved');
        $this->dispatch('notify', ['type' => 'success', 'message' => 'تم تعديل التسلسل بنجاح.']);
        return redirect()->route('agency.approval-sequences');

    }

    public function addEditApproverField()
    {
        $this->editApprovers[] = '';
    }

    public function removeEditApproverField($index)
    {
        unset($this->editApprovers[$index]);
        $this->editApprovers = array_values($this->editApprovers);
    }

    public function openAddModal()
    {
        $this->reset(['name', 'action_type', 'approvers']);
        $this->approvers = [''];
        $this->showAddModal = true;
        $this->dispatch('openAddModal');
    }

    public function closeAddModal()
    {
        $this->reset(['name', 'action_type', 'approvers']);
        $this->showAddModal = false;
        $this->dispatch('closeAddModal');
    }
    public function closeEditModal()
    {
        $this->reset(['editName', 'editActionType', 'editApprovers']);
        $this->showEditModal = false;
        $this->dispatch('closeEditModal');
    }
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->dispatch('closeViewModal');
    }
    public function addApproverField()
    {
        $this->approvers[] = '';
    }

    public function removeApproverField($index)
    {
        unset($this->approvers[$index]);
        $this->approvers = array_values($this->approvers);
    }
    
public function saveSequence()
{
    $this->validate([
        'name' => 'required|string|max:255',
        'action_type' => 'required|string|max:255',
        'approvers' => 'required|array|min:1',
        'approvers.*' => 'required|exists:users,id',
    ]);

    $sequence = ApprovalSequence::create([
        'agency_id' => auth()->user()->agency_id,
        'name' => $this->name,
        'action_type' => $this->action_type,
    ]);

    foreach ($this->approvers as $index => $userId) {
        $sequence->users()->create([
            'user_id' => $userId,
            'step_order' => $index + 1,
        ]);
    }

    $this->dispatch('notify', ['type' => 'success', 'message' => 'تم إضافة التسلسل بنجاح.']);
    return redirect()->route('agency.approval-sequences');
}



    public function render()
    {
        $users = \App\Models\User::where('agency_id', Auth::user()->agency_id)->get();

        return view('livewire.agency.approval-sequences', [
            'sequences' => ApprovalSequence::where('agency_id', Auth::user()->agency_id)
                ->orderBy('created_at', 'desc')
                ->paginate(10),
            'users' => $users,
        ]);
    }
}
