<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewProviderApprovalRequest extends Notification
{
    use Queueable;

    protected $approvalRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct($approvalRequest)
    {
        $this->approvalRequest = $approvalRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('طلب موافقة على إضافة مزود جديد')
            ->line('تم تقديم طلب إضافة مزود جديد من أحد الفروع ويحتاج لموافقتك.')
            ->action('عرض الطلب', url('/admin/approval-requests'))
            ->line('يرجى مراجعة الطلب واتخاذ الإجراء المناسب.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // جلب اسم الفرع أو الوكالة المرسلة
        $agencyName = $this->approvalRequest->agency?->name ?? 'فرع غير معروف';
        // جلب اسم المزود إذا توفر
        $providerName = $this->approvalRequest->model?->name ?? 'مزود جديد';
        return [
            'approval_request_id' => $this->approvalRequest->id,
            'model_type' => $this->approvalRequest->model_type,
            'model_id' => $this->approvalRequest->model_id,
            'message' => 'فرع/وكالة "' . $agencyName . '" أضاف المزود "' . $providerName . '" ويحتاج لموافقتك.',
            'url' => route('agency.approvals.index'),
        ];
    }
}
