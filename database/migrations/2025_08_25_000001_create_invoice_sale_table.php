<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('invoice_sale')) {
            Schema::create('invoice_sale', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
                $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
                $table->decimal('base_amount',12,2)->default(0);
                $table->boolean('tax_is_percent')->default(true);
                $table->decimal('tax_input',12,2)->default(0);
                $table->decimal('tax_amount',12,2)->default(0);
                $table->decimal('line_total',12,2)->default(0);
                $table->timestamps();
                $table->unique(['invoice_id','sale_id']);
            });
        }
    }
    public function down(): void { Schema::dropIfExists('invoice_sale'); }
};
