<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_monthly_targets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('year');   // مثال: 2025
            $t->unsignedTinyInteger('month');   // 1..12
            $t->decimal('main_target', 12, 2)->default(0);   // الهدف الأساسي للحوافز
            $t->decimal('sales_target', 12, 2)->default(0);  // هدف المقارنة فقط
            $t->decimal('override_rate', 8, 2)->nullable();  // % اختيارية تُغلب profile
            $t->boolean('locked')->default(false);           // قفل بعد الإقفال الشهري
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique(['user_id','year','month']);
            $t->index(['agency_id','year','month']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('employee_monthly_targets');
    }
};