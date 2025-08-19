<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 14, 2)->default(0);
            $table->enum('status', ['active','frozen'])->default('active');
            $table->timestamps();

            $table->unique('user_id');
            $table->index('status');
        });
    }
    public function down(): void {
        Schema::dropIfExists('employee_wallets');
    }
};
