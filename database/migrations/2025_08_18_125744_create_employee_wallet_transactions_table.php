<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('employee_wallets')->onDelete('cascade');

            // الأنواع الأساسية المؤثرة على حساب الموظف
            $table->enum('type', [
                'commission_earned',   // عُمولة موظف عند تحقُّقها
                'commission_held',     // حجز عمولة (لا تغيّر الرصيد إن أردت، لكن نحتفظ بها كسجل)
                'debt_charge',         // تقييد دين على الموظف (مبيعات متأخرة)
                'debt_payment',        // سداد/إلغاء دين بعد التحصيل
                'collector_commission',// عمولة مُحصِّل
                'deposit', 'withdraw', 'adjust' // إداري/تسوية
            ]);

            $table->decimal('amount', 14, 2);          // > 0
            $table->decimal('running_balance', 14, 2); // الرصيد بعد الحركة
            $table->string('reference')->nullable();   // مثال: sale:{id} أو collect:{id}
            $table->text('note')->nullable();
            $table->string('performed_by_name')->nullable();
            $table->timestamps();

            $table->index(['wallet_id','created_at']);
            $table->index('type');
            $table->unique(['wallet_id','reference']); // لمنع التكرار لنفس العملية (اختياري وقوي)
        });
    }
    public function down(): void {
        Schema::dropIfExists('employee_wallet_transactions');
    }
};
