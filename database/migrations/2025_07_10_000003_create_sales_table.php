<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // العلاقات
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('service_type_id')->constrained('dynamic_list_items')->onDelete('restrict');
            $table->foreignId('provider_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('intermediary_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('account_id')->nullable()->constrained()->onDelete('set null');

            // بيانات العملية
            $table->date('sale_date');
            $table->string('beneficiary_name')->nullable();
            $table->string('route')->nullable();
            $table->string('pnr')->nullable();
            $table->string('reference')->nullable();

            $table->string('status')->nullable(); // ✅ بدل "action"
            $table->string('payment_method')->nullable(); // طريقة الدفع
            $table->string('payment_type')->nullable();   // وسيلة الدفع
            $table->string('receipt_number')->nullable(); // رقم السند
            $table->string('phone_number')->nullable();   // رقم الهاتف
            $table->decimal('commission', 12, 2)->nullable(); // العمولة

            $table->decimal('usd_buy', 12, 2)->nullable();   // سعر الشراء
            $table->decimal('usd_sell', 12, 2)->nullable();  // سعر البيع
            $table->decimal('amount_paid', 12, 2)->nullable(); // بدل amount_received
            $table->string('depositor_name')->nullable();   // اسم المودع
            $table->decimal('sale_profit', 12, 2)->nullable(); // الربح

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
