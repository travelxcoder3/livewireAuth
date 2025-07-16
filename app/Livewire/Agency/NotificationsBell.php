<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationsBell extends Component
{
    public $lastNotificationId = null;

    protected $listeners = ['refreshNotifications' => '$refresh'];

    public function render()
    {
        $notifications = Auth::user()->unreadNotifications()->latest()->take(10)->get();

        // إذا كان هناك إشعار جديد لم يكن ظاهرًا من قبل
        if ($notifications->count() > 0) {
            $latestId = $notifications->first()->id;
            if ($this->lastNotificationId && $latestId !== $this->lastNotificationId) {
                // أطلق حدث Toast
                $message = $notifications->first()->data['message'] ?? 'لديك إشعار جديد';
                $this->dispatch('new-notification-toast', [
                    'message' => $message,
                ]);
            }
            $this->lastNotificationId = $latestId;
        }

        return view('livewire.agency.notifications-bell', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->unreadNotifications()->find($notificationId);
        if ($notification) {
            $url = $notification->data['url'] ?? null;
            $notification->markAsRead();
            if ($url) {
                $this->dispatch('redirect-to-url', ['url' => $url]);
            }
        }
    }
} 