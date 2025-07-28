<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateRefundStatusToFullAndPartialInSalesTable extends Migration
{
    public function up(): void
    {
        // تحديث البيانات القديمة
        DB::table('sales')->where('status', 'Refund')->update(['status' => 'Refund-Full']);

        // تعديل ENUM
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM(
            'Issued',
            'Re-Issued',
            'Re-Route',
            'Refund-Full',
            'Refund-Partial',
            'Void',
            'Applied',
            'Rejected',
            'Approved'
        ) NULL");
    }

    public function down(): void
    {
        // إعادة البيانات القديمة
        DB::table('sales')->where('status', 'Refund-Full')->update(['status' => 'Refund']);
        DB::table('sales')->where('status', 'Refund-Partial')->update(['status' => 'Refund']);

        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM(
            'Issued',
            'Re-Issued',
            'Re-Route',
            'Refund',
            'Void',
            'Applied',
            'Rejected',
            'Approved'
        ) NULL");
    }
}
