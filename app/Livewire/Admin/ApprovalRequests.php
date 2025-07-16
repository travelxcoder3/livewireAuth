<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ApprovalRequest;
use App\Models\Provider;
use App\Notifications\ProviderApprovalResult;
use Illuminate\Support\Facades\Notification;

class ApprovalRequests extends Component
{
    public function approve($id)
    {
        $request = ApprovalRequest::findOrFail($id);
        $request->status = 'approved';
        $request->save();

        $provider = Provider::find($request->model_id);
        if ($provider) {
            $provider->status = 'approved';
            $provider->save();
            // إشعار للفرع
            $user = $request->requested_by_user ?? $request->requestedBy; // حسب العلاقة
            if ($user) {
                Notification::send($user, new ProviderApprovalResult($provider, 'approved'));
            }
        }
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
            // إشعار للفرع
            $user = $request->requested_by_user ?? $request->requestedBy;
            if ($user) {
                Notification::send($user, new ProviderApprovalResult($provider, 'rejected'));
            }
        }
    }

    public function render()
    {
        $mainAgency = auth()->user()->agency;
        // إذا كان المستخدم في الوكالة الرئيسية
        $agencyIds = $mainAgency->branches()->pluck('id')->toArray();
        $agencyIds[] = $mainAgency->id;
        $requests = \App\Models\ApprovalRequest::where('status', 'pending')
            ->whereIn('agency_id', $agencyIds)
            ->latest()->get();
        return view('livewire.admin.approval-requests', compact('requests'))
            ->layout('layouts.agency');
    }
}
