<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('wallet_transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
        $table->enum('type', ['deposit','withdraw','adjust','transfer_in','transfer_out','refund']);
        $table->decimal('amount', 14, 2);          // > 0
        $table->decimal('running_balance', 14, 2); // الرصيد بعد الحركة
        $table->string('reference')->nullable();
        $table->text('note')->nullable();
        // يلتقط اسم المنفّذ تلقائياً من Auth::user()->name
        $table->string('performed_by_name')->nullable();
        $table->timestamps();

        $table->index(['wallet_id','created_at']);
        $table->index('type');
    });
}

public function down(): void
{
    Schema::dropIfExists('wallet_transactions');
}

};
