<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SaleEditApprovalResult extends Notification
{
    use Queueable; // لا نستخدم ShouldQueue

    public function __construct(public Sale $sale, public string $result) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $message = $this->result === 'approved'
            ? "تمت الموافقة على طلب تعديل البيع #{$this->sale->id}"
            : "تم رفض طلب تعديل البيع #{$this->sale->id}";

        return [
            'type'    => 'sale_edit',
            'result'  => $this->result,     // approved | rejected
            'sale_id' => $this->sale->id,

            // للعرض في الجرس/التوست
            'message' => $message,

            // احتفاظ بالحقول السابقة إن احتجتها في أي مكان
            'title'   => $this->result === 'approved'
                        ? 'تمت الموافقة على طلب تعديل البيع'
                        : 'تم رفض طلب تعديل البيع',
            'body'    => 'رقم العملية: #' . $this->sale->id,

            // اختياري: يفيد مع markAsRead() إن أردت تحويلًا تلقائيًا
            'url'     => route('agency.approvals.index'),
        ];
    }
}
