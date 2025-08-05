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
    Schema::table('sales', function (Blueprint $table) {
        $table->foreignId('updated_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('sales', function (Blueprint $table) {
        $table->dropForeign(['updated_by']);
        $table->dropColumn('updated_by');
    });
}
};