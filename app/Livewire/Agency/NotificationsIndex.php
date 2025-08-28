<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;

class NotificationsIndex extends Component
{
    use WithPagination;

    public string $tab = 'unread'; // unread | all
    protected $queryString = ['tab'];
    protected $listeners   = ['refreshNotifications' => '$refresh'];

    /** أساس الاستعلام (كل إشعارات المستخدم الحالي) */
    private function baseQuery()
    {
        return AppNotification::query()->where('user_id', Auth::id());
    }

    /** عند تغيير التاب أعِد الصفحة للأولى */
    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function markAllAsRead(): void
    {
        $this->baseQuery()
            ->whereNull('read_at')
            ->update(['is_read' => true, 'read_at' => now()]);

        $this->dispatch('refreshNotifications');
        $this->resetPage();
    }

    public function markAsRead(int $id)
    {
        $n = $this->baseQuery()->findOrFail($id);

        if (!$n->is_read) {
            $n->update(['is_read' => true, 'read_at' => now()]);
        }

        $this->dispatch('refreshNotifications');

        if (!empty($n->url)) {
            return $this->redirect($n->url, navigate: true);
        }

        // تحديث القائمة الحالية بدون انتقال
        $this->resetPage();
    }

    public function render()
    {
        $q = $this->baseQuery()->latest('id');

        if ($this->tab === 'unread') {
            $q->whereNull('read_at');
        }

        $items = $q->paginate(15);

        // ملاحظة: عدّل المسار ليتوافق مع مكان ملف الـ Blade لديك
        return view('livewire.agency.notifications-index', compact('items'))
            ->layout('layouts.agency');
    }
}
