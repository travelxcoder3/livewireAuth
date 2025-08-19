<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('commission_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->string('name')->default('الافتراضي');
            $t->boolean('is_active')->default(true)->index();
            $t->decimal('employee_rate', 8, 2)->default(0); // % من صافي الربح
            $t->unsignedInteger('days_to_debt')->default(30);
            $t->enum('debt_behavior', ['deduct_commission_until_paid','hold_commission'])
              ->default('deduct_commission_until_paid');
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('commission_profiles');
    }
};

