<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
        // اسمح بالدخول فقط لمن لديه تسلسل موافقات (أو سوبر أدمن حسب الـ Gate)
        abort_unless(Gate::allows('approvals.access'), 403);

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

        DB::transaction(function () use ($request) {
            $request->update(['status' => 'approved']);

            // مزوّد (السلوك القديم)
            if ($request->model_type === Provider::class) {
                if ($provider = Provider::find($request->model_id)) {
                    $provider->update(['status' => 'approved']);

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
                $this->dispatch('approval-state-updated');

            }
        });

        $this->markNotificationAsReadIfExists();
        return redirect()->route('agency.approvals.index');
    }

    public function reject(int $id)
    {
        $request = ApprovalRequest::findOrFail($id);

        DB::transaction(function () use ($request) {
            $request->update(['status' => 'rejected']);

            if ($request->model_type === Provider::class) {
                if ($provider = Provider::find($request->model_id)) {
                    $provider->update(['status' => 'rejected']);

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
                $this->dispatch('approval-state-updated');

            }
        });

        $this->markNotificationAsReadIfExists();
        return redirect()->route('agency.approvals.index');
    }

    /** إشعار صاحب الطلب وجميع الموافقين بنتيجة الاعتماد/الرفض */
    private function notifySaleResult(ApprovalRequest $request, Sale $sale, string $result): void
    {
        // صاحب الطلب
        $requestor = $request->requestedBy ?: User::find($request->requested_by);

        // كل المستخدمين المعيّنين كموافقين في هذا التسلسل
        $approverIds = ApprovalSequenceUser::where('approval_sequence_id', $request->approval_sequence_id)
            ->pluck('user_id');

        // اجمع: صاحب الطلب + الموافقين + (الموافق الحالي الذي نفذ العملية)
        $notifyIds = collect([$requestor?->id, Auth::id()])
            ->merge($approverIds)
            ->unique()
            ->filter()
            ->values();

        if ($notifyIds->isEmpty()) {
            return;
        }

        // إشعار Laravel (notifications الجدول)
        $users = User::whereIn('id', $notifyIds)->get();
        if ($users->isNotEmpty()) {
            Notification::send($users, new SaleEditApprovalResult($sale, $result));
        }

        // إشعار نظامك المخصص (app_notifications)
        $title = $result === 'approved'
            ? 'تمت الموافقة على طلب تعديل البيع'
            : 'تم رفض طلب تعديل البيع';

        $body = "هناك تحديث على طلب تعديل العملية رقم #{$sale->id}";

        Notify::toUsers(
            $notifyIds->all(),
            $title,
            $body,
            route('agency.notifications.index'), // واجهة الإشعارات
            'sale_edit',
            Auth::user()->agency_id
        );

        // حدّث جرس الإشعارات فورًا
        $this->dispatch('refreshNotifications');
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

        // جميع التسلسلات التي المستخدم الحالي أحد الموافقين فيها (ضمن نفس الوكالة)
        $sequenceIds = DB::table('approval_sequence_users')->where('user_id', $userId)->pluck('approval_sequence_id');

        // الطلبات المعلّقة التي تنتظر موافقته
        $requests = ApprovalRequest::where('status', 'pending')
            ->whereIn('approval_sequence_id', $sequenceIds)
            ->latest()
            ->get();

        return view('livewire.agency.approval-requests', compact('requests'))
            ->layout('layouts.agency');
    }
}
