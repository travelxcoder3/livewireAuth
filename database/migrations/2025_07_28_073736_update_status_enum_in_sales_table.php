<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStatusEnumInSalesTable extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM(
            'issued',
            'refunded',
            'canceled',
            'pending',
            'reissued',
            'void',
            'paid',
            'unpaid'
        ) NULL");
    }
}