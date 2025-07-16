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
            

            // بيانات العملية
            $table->date('sale_date');
            $table->string('beneficiary_name')->nullable();
            $table->string('route')->nullable();
            $table->string('pnr')->nullable();
            $table->string('reference')->nullable();

            $table->enum('status', [
                'issued',
                'refunded',
                'canceled',
                'pending',
                'reissued',
                'void',
                'paid',
                'unpaid',
            ])->nullable();       
            $table->enum('payment_method', ['kash', 'part', 'all'])->nullable();
            $table->enum('payment_type', ['creamy', 'kash', 'visa'])->nullable()->comment('وسيلة الدفع');
            $table->string('receipt_number')->nullable(); 
            $table->string('phone_number')->nullable();  
            $table->decimal('commission', 12, 2)->nullable();
            $table->enum('customer_via', ['whatsapp', 'viber', 'instagram', 'other'])->nullable();
            $table->decimal('usd_buy', 12, 2)->nullable();  
            $table->decimal('usd_sell', 12, 2)->nullable();  
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->string('depositor_name')->nullable(); 
            $table->decimal('sale_profit', 12, 2)->nullable();
            
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
