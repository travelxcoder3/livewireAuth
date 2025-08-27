<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SaleEditApprovalPending extends Notification
{
    use Queueable;

    public function __construct(public Sale $sale, public string $requestedByName) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'sale_edit_pending',
            'message' => "وصلك طلب تعديل عملية بيع #{$this->sale->id} من {$this->requestedByName}",
            'sale_id' => $this->sale->id,
            'url'     => route('agency.approvals.index'),
        ];
    }
}
