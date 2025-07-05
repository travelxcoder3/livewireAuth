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
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_name_guard_name_unique');
            $table->unique(['name', 'guard_name', 'agency_id'], 'permissions_name_guard_name_agency_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_name_guard_name_agency_id_unique');
            $table->unique(['name', 'guard_name'], 'permissions_name_guard_name_unique');
        });
    }
};
