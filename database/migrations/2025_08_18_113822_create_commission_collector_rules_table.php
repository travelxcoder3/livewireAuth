<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('commission_collector_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('profile_id')->constrained('commission_profiles')->cascadeOnDelete();
            $t->unsignedTinyInteger('method')->nullable(); // null=بدون تحصيل
            $t->enum('type', ['percent','fixed'])->nullable();
            $t->decimal('value', 12, 2)->nullable();
            $t->enum('basis', ['collected_amount','net_margin','employee_commission'])->nullable();
            $t->timestamps();

            $t->unique(['profile_id','method']);     // قاعدة لكل طريقة
            $t->index(['profile_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('commission_collector_rules');
    }
};
