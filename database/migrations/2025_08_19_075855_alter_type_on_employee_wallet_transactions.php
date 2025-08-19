<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // يتطلب doctrine/dbal للتغيير
        Schema::table('employee_wallet_transactions', function (Blueprint $table) {
            $table->string('type', 32)->change();
        });
    }
    public function down(): void
    {
        // أعده لما كان عليه لديك (ENUM) إن رغبت
        // مثال تقريبي، عدّل القيم حسب حقلِك القديم:
        Schema::table('employee_wallet_transactions', function (Blueprint $table) {
            $table->enum('type', ['deposit','withdraw','adjust','sale_debt','debt_release'])->change();
        });
    }
};
