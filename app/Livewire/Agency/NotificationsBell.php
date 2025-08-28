<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\AppNotification;

class NotificationsBell extends Component
{
    public $lastNotificationId = null;
    protected $listeners = ['refreshNotifications' => '$refresh'];

    public function render()
    {
        $notifications = AppNotification::forUser(Auth::id())
            ->unread()->latest('id')->take(5)->get();

        if ($notifications->count()) {
            $latestId = (string)$notifications->first()->id;
            if ($this->lastNotificationId && $latestId !== $this->lastNotificationId) {
                $msg = $notifications->first()->title ?? 'إشعار جديد';
                $this->dispatch('new-notification-toast', ['message' => $msg]);
            }
            $this->lastNotificationId = $latestId;
        }

        return view('livewire.agency.notifications-bell', compact('notifications'));
    }

    public function markAsRead($id)
{
    $n = AppNotification::forUser(Auth::id())->find($id);
    if ($n) {
        $n->update(['is_read'=>true,'read_at'=>now()]);
        $this->dispatch('redirect-to-url', [
            'url' => $n->url ?: route('agency.notifications.index')
        ]);
        $this->dispatch('refreshNotifications');
    }
}
}