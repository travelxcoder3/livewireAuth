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
        Schema::table('invoice_sale', function (Blueprint $table) {
            $table->decimal('base_amount',12,2)->default(0);
            $table->boolean('tax_is_percent')->default(true);
            $table->decimal('tax_input',12,2)->default(0);   // 5 = 5%
            $table->decimal('tax_amount',12,2)->default(0);  // المبلغ المحتسب
            $table->decimal('line_total',12,2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_sale', function (Blueprint $table) {
            //
        });
    }
};
