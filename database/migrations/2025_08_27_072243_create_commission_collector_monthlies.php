<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('commission_collector_monthlies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('year');
            $t->unsignedTinyInteger('month');
            $t->unsignedTinyInteger('method'); // 1..8
            $t->enum('type', ['percent','fixed'])->nullable();
            $t->decimal('value', 12, 4)->nullable();
            $t->enum('basis', ['collected_amount','net_margin','employee_commission'])->nullable();
            $t->boolean('locked')->default(true);
            $t->timestamps();

            $t->unique(['agency_id','year','month','method'], 'ccm_agy_yr_mo_meth_uq');
            $t->index(['agency_id','year','month'], 'ccm_agy_yr_mo_idx');

        });
    }
    public function down(): void {
        Schema::dropIfExists('commission_collector_monthlies');
    }
};
