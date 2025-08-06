<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\ApprovalRequest;
use App\Models\Provider;
use App\Notifications\ProviderApprovalResult;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;

class ApprovalRequests extends Component
{
    public $notification_id;

    public function mount($notification_id = null)
    {
        $this->notification_id = $notification_id;
        if ($notification_id) {
            $notification = Auth::user()->notifications()->find($notification_id);
            if ($notification) {
                $notification->markAsRead();
            }
        }
    }

    public function approve($id)
    {
        $request = ApprovalRequest::findOrFail($id);
        $request->status = 'approved';
        $request->save();

        $provider = Provider::find($request->model_id);
        if ($provider) {
            $provider->status = 'approved';
            $provider->save();
            $user = $request->requested_by_user ?? $request->requestedBy;
            if ($user) {
                Notification::send($user, new ProviderApprovalResult($provider, 'approved'));
            }
            // بث حدث Livewire لتحديث المزودين في الفروع
            $this->dispatch('providerStatusUpdated')->to('App\\Livewire\\Agency\\Providers');
        }
        $this->markNotificationAsReadIfExists();
        return redirect()->route('agency.approvals.index');
    }

    public function reject($id)
    {
        $request = ApprovalRequest::findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        $provider = Provider::find($request->model_id);
        if ($provider) {
            $provider->status = 'rejected';
            $provider->save();
            $user = $request->requested_by_user ?? $request->requestedBy;
            if ($user) {
                Notification::send($user, new ProviderApprovalResult($provider, 'rejected'));
            }
            // بث حدث Livewire لتحديث المزودين في الفروع
            $this->dispatch('providerStatusUpdated')->to('App\\Livewire\\Agency\\Providers');
        }
        $this->markNotificationAsReadIfExists();
        return redirect()->route('agency.approvals.index');
    }

    protected function markNotificationAsReadIfExists()
    {
        if ($this->notification_id) {
            $notification = Auth::user()->notifications()->find($this->notification_id);
            if ($notification) {
                $notification->markAsRead();
                $this->notification_id = null;
                $this->dispatch('refreshNotifications');
            }
        }
    }

    public function render()
    {
        $userId = Auth::id();
    
        // احصل على كل التسلسلات التي هذا المستخدم مخول فيها
        $sequenceIds = \DB::table('approval_sequence_users')
            ->where('user_id', $userId)
            ->pluck('approval_sequence_id');
    
        // الطلبات التي تنتظر موافقة هذا المستخدم
        $requests = \App\Models\ApprovalRequest::where('status', 'pending')
            ->whereIn('approval_sequence_id', $sequenceIds)
            ->latest()
            ->get();
    
        return view('livewire.agency.approval-requests', compact('requests'))
            ->layout('layouts.agency');
    }
    
} 