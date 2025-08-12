<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('quotation_number')->unique();
            $t->date('quotation_date');
            $t->string('to_client')->nullable();
            $t->string('tax_name')->nullable();
            $t->decimal('tax_rate',5,2)->default(0);
            $t->string('currency',8)->default('USD');
            $t->string('lang',2)->default('en');
            $t->text('notes')->nullable();
            $t->decimal('subtotal',12,2)->default(0);
            $t->decimal('tax_amount',12,2)->default(0);
            $t->decimal('grand_total',12,2)->default(0);
            $t->timestamps();

            $t->index(['agency_id','quotation_date','id'],'quotations_agency_date_id_idx');
        });

         Schema::create('quotation_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $t->string('service_name')->nullable();
            $t->string('description')->nullable();
            $t->string('route')->nullable();
            $t->string('service_date')->nullable();
            $t->string('conditions')->nullable();
            $t->decimal('price',12,2)->default(0);
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });

        Schema::create('quotation_terms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $t->text('value');
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('quotation_terms');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
