<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

use App\Models\User;
use App\Models\Sale;
use App\Models\Provider;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSequenceUser;
use App\Services\Notify;


use App\Notifications\ProviderApprovalResult;
use App\Notifications\SaleEditApprovalResult;

class ApprovalRequests extends Component
{
    public ?string $notification_id = null;

    public function mount(?string $notification_id = null): void
    {
        
        // يدعم باراميتر Livewire + الـ query string
        $this->notification_id = $notification_id ?? request('notification_id');

        if ($this->notification_id) {
            $n = Auth::user()->notifications()->find($this->notification_id);
            if ($n && is_null($n->read_at)) {
                $n->markAsRead();
                $this->dispatch('refreshNotifications');
            }
        }
    }

    public function approve(int $id)
    {
        $request = ApprovalRequest::findOrFail($id);
        $request->status = 'approved';
        $request->save();

        // مزوّد (السلوك الموجود سابقاً)
        if ($request->model_type === Provider::class) {
            if ($provider = Provider::find($request->model_id)) {
                $provider->status = 'approved';
                $provider->save();

                $requestor = $request->requestedBy ?: User::find($request->requested_by);
                if ($requestor) {
                    Notification::send($requestor, new ProviderApprovalResult($provider, 'approved'));
                }
                $this->dispatch('providerStatusUpdated')->to('App\\Livewire\\Agency\\Providers');
            }
        }

        // تعديل بيع: أشعِر صاحب الطلب + كل الموافقين + المنفّذ الحالي
        if ($request->model_type === Sale::class) {
            if ($sale = Sale::find($request->model_id)) {
                $this->notifySaleResult($request, $sale, 'approved');
            }
        }

        $this->markNotificationAsReadIfExists();
        return redirect()->route('agency.approvals.index');
    }

    public function reject(int $id)
    {
        $request = ApprovalRequest::findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        if ($request->model_type === Provider::class) {
            if ($provider = Provider::find($request->model_id)) {
                $provider->status = 'rejected';
                $provider->save();

                $requestor = $request->requestedBy ?: User::find($request->requested_by);
                if ($requestor) {
                    Notification::send($requestor, new ProviderApprovalResult($provider, 'rejected'));
                }
                $this->dispatch('providerStatusUpdated')->to('App\\Livewire\\Agency\\Providers');
            }
        }

        if ($request->model_type === Sale::class) {
            if ($sale = Sale::find($request->model_id)) {
                $this->notifySaleResult($request, $sale, 'rejected');
            }
        }

        $this->markNotificationAsReadIfExists();
        return redirect()->route('agency.approvals.index');
    }

    /** إشعار صاحب الطلب وجميع الموافقين بنتيجة الاعتماد/الرفض */
    private function notifySaleResult(ApprovalRequest $request, Sale $sale, string $result): void
    {
        $requestor   = $request->requestedBy ?: User::find($request->requested_by);
        $approverIds = ApprovalSequenceUser::where('approval_sequence_id', $request->approval_sequence_id)
            ->pluck('user_id');

        $notifyIds = collect([$requestor?->id, Auth::id()])
            ->merge($approverIds)
            ->unique()
            ->filter()
            ->values();

        if ($notifyIds->isEmpty()) return;

        $users = User::whereIn('id', $notifyIds)->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new SaleEditApprovalResult($sale, $result));
            $title = $result === 'approved'
    ? 'تمت الموافقة على طلب تعديل البيع'
    : 'تم رفض طلب تعديل البيع';

$body = "هناك تحديث على طلب تعديل العملية رقم #{$sale->id}";

Notify::toUsers(
    $notifyIds->all(),                            // صاحب الطلب + جميع الموافقين + من نفّذ الإجراء
    $title,
    $body,
    route('agency.notifications.index'),        // واجهة الإشعارات
    'sale_edit',
    Auth::user()->agency_id
);

            $this->dispatch('refreshNotifications'); // يحدث عداد الجرس فوراً
        }
    }

    protected function markNotificationAsReadIfExists(): void
    {
        if (!$this->notification_id) return;

        $n = Auth::user()->notifications()->find($this->notification_id);
        if ($n && is_null($n->read_at)) {
            $n->markAsRead();
        }

        $this->notification_id = null;
        $this->dispatch('refreshNotifications');
    }

    public function render()
    {
        $userId = Auth::id();

        $sequenceIds = DB::table('approval_sequence_users')
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
