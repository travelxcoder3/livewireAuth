<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\AppNotification;

class BellCounter extends Component
{
    protected $listeners = ['refreshNotifications' => '$refresh'];

    public function render()
    {
        $count = AppNotification::forUser(Auth::id())->unread()->count();
        return view('livewire.agency.bell-counter', compact('count'));
    }
}
