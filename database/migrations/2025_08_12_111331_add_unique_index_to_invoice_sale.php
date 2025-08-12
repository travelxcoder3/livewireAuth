<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_sale', function (Blueprint $table) {
            $table->unique(['invoice_id','sale_id'], 'inv_sale_invoice_sale_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_sale', function (Blueprint $table) {
            $table->dropUnique('inv_sale_invoice_sale_unique');
        });
    }
};
