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
        $agency = auth()->user()->agency;

        if ($agency->parent_id) {
            // المستخدم في فرع: يعرض فقط طلبات الفرع
            $agencyIds = [$agency->id];
        } else {
            // المستخدم في الوكالة الرئيسية: يعرض طلبات الوكالة وكل الفروع التابعة لها
            $branchIds = $agency->branches()->pluck('id')->toArray();
            $agencyIds = array_merge([$agency->id], $branchIds);
        }

        $requests = \App\Models\ApprovalRequest::where('status', 'pending')
            ->whereIn('agency_id', $agencyIds)
            ->latest()->get();
        return view('livewire.agency.approval-requests', compact('requests'))
            ->layout('layouts.agency');
    }
} 