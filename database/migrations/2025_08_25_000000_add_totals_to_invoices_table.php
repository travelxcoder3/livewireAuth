<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices','subtotal'))   $table->decimal('subtotal',12,2)->default(0)->after('agency_id');
            if (!Schema::hasColumn('invoices','tax_total'))  $table->decimal('tax_total',12,2)->default(0)->after('subtotal');
            if (!Schema::hasColumn('invoices','grand_total'))$table->decimal('grand_total',12,2)->default(0)->after('tax_total');
        });
    }
    public function down(): void {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices','grand_total')) $table->dropColumn('grand_total');
            if (Schema::hasColumn('invoices','tax_total'))   $table->dropColumn('tax_total');
            if (Schema::hasColumn('invoices','subtotal'))    $table->dropColumn('subtotal');
        });
    }
};
