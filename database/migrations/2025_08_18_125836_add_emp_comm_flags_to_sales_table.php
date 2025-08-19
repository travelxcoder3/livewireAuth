<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('employee_commission_posted_at')->nullable();
            $table->timestamp('employee_debt_charged_at')->nullable();
            $table->timestamp('employee_debt_cleared_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['employee_commission_posted_at','employee_debt_charged_at','employee_debt_cleared_at']);
        });
    }
};
