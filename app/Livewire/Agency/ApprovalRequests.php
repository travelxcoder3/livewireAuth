<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\ApprovalRequest;
use App\Models\Provider;
use App\Models\Sale;                           // ✅ استيراد Sale هنا فقط (خارج الكلاس)
use App\Notifications\ProviderApprovalResult;
use App\Notifications\SaleEditApprovalResult; // ✅ استيراد الإشعار هنا
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\ApprovalSequenceUser;
use App\Models\User;



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
    $request = \App\Models\ApprovalRequest::findOrFail($id);
    $request->status = 'approved';
    $request->save();

    // لو كان مزود - السلوك القديم
    if ($request->model_type === \App\Models\Provider::class) {
        // ... (كما هو عندك)
    }

    // لو كان تعديل بيع: أشعِر "الجهتين"
    if ($request->model_type === Sale::class) {
        $sale = Sale::find($request->model_id);

        // صاحب الطلب
        $requestor = $request->requested_by_user ?? $request->requestedBy;

        // جميع الموافقين ضمن التسلسل
        $approverIds = ApprovalSequenceUser::where('approval_sequence_id', $request->approval_sequence_id)
            ->pluck('user_id');

        $notifyIds = collect([$requestor?->id])->merge($approverIds)->unique()->filter();
        $users = User::whereIn('id', $notifyIds)->get();

        if ($users->isNotEmpty() && $sale) {
            Notification::send($users, new SaleEditApprovalResult($sale, 'approved'));
        }
    }

    $this->markNotificationAsReadIfExists();
    return redirect()->route('agency.approvals.index');
}

public function reject($id)
{
    $request = \App\Models\ApprovalRequest::findOrFail($id);
    $request->status = 'rejected';
    $request->save();

    if ($request->model_type === \App\Models\Provider::class) {
        // ... (كما هو عندك)
    }

    if ($request->model_type === Sale::class) {
        $sale = Sale::find($request->model_id);
        $requestor = $request->requested_by_user ?? $request->requestedBy;

        $approverIds = ApprovalSequenceUser::where('approval_sequence_id', $request->approval_sequence_id)
            ->pluck('user_id');

        $notifyIds = collect([$requestor?->id])->merge($approverIds)->unique()->filter();
        $users = User::whereIn('id', $notifyIds)->get();

        if ($users->isNotEmpty() && $sale) {
            Notification::send($users, new SaleEditApprovalResult($sale, 'rejected'));
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
        $userId = Auth::id();

        $sequenceIds = \DB::table('approval_sequence_users')
            ->where('user_id', $userId)
            ->pluck('approval_sequence_id');

        $requests = ApprovalRequest::where('status', 'pending')
            ->whereIn('approval_sequence_id', $sequenceIds)
            ->latest()
            ->get();

        return view('livewire.agency.approval-requests', compact('requests'))
            ->layout('layouts.agency');
    }
}
