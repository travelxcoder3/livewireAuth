<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // فهرس للعملاء: (الوكالة، الاسم)
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['agency_id', 'name'], 'customers_agency_name_idx');
        });

        // فهرس للمزودين: (الوكالة، الحالة، الاسم)
        Schema::table('providers', function (Blueprint $table) {
            $table->index(['agency_id', 'status', 'name'], 'providers_agency_status_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_agency_name_idx');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropIndex('providers_agency_status_name_idx');
        });
    }
};