<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    // ✅ أولاً: تعديل enum ليشمل القيم الجديدة، حتى يقبل 'wallet'
    DB::statement("ALTER TABLE sales MODIFY payment_type ENUM(
        'cash', 
        'transfer', 
        'account_deposit', 
        'fund', 
        'from_account', 
        'wallet', 
        'other',
        'creamy' -- مؤقتاً نترك creamy موجودة عشان نقدر نحولها
    ) NULL COMMENT 'وسيلة الدفع'");

    // ✅ تعديل القيم الآن
    DB::table('sales')
        ->where('payment_type', 'creamy')
        ->update(['payment_type' => 'wallet']);

    // ✅ إعادة تعريف الحقل بدون القيمة القديمة
    DB::statement("ALTER TABLE sales MODIFY payment_type ENUM(
        'cash', 
        'transfer', 
        'account_deposit', 
        'fund', 
        'from_account', 
        'wallet', 
        'other'
    ) NULL COMMENT 'وسيلة الدفع'");

    // ✅ تعديل customer_via مباشرة لأنه ما فيه مشكلة حالياً
    DB::statement("ALTER TABLE sales MODIFY customer_via ENUM(
        'facebook', 
        'call', 
        'instagram', 
        'whatsapp', 
        'office', 
        'other'
    ) NULL");
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة القيم الأصلية القديمة
        DB::statement("ALTER TABLE sales MODIFY payment_type ENUM(
            'creamy', 
            'kash', 
            'visa'
        ) NULL COMMENT 'وسيلة الدفع'");

        DB::statement("ALTER TABLE sales MODIFY customer_via ENUM(
            'whatsapp', 
            'viber', 
            'instagram', 
            'other'
        ) NULL");
    }
};