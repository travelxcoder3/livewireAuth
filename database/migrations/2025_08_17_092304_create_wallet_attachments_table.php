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
    Schema::create('wallet_attachments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('transaction_id')->constrained('wallet_transactions')->onDelete('cascade');
        $table->string('path');                 // يُنشأ فقط عند وجود ملف، غير إجباري على مستوى النظام
        $table->string('mime')->nullable();
        $table->unsignedBigInteger('size')->nullable();
        $table->timestamps();

        $table->index('transaction_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('wallet_attachments');
}

};
