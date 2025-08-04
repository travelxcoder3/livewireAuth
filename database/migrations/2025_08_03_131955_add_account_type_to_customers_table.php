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
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('account_type', ['individual', 'company', 'organization'])
                  ->default('individual')
                  ->after('has_commission');

            $table->decimal('opening_balance', 12, 2)
                  ->default(0)
                  ->after('account_type'); // بعد نوع الحساب
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('account_type');
            $table->dropColumn('opening_balance');
        });
    }
};
