<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('sales', function (Blueprint $table) {
        $table->foreignId('duplicated_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('sales', function (Blueprint $table) {
        $table->dropForeign(['duplicated_by']);
        $table->dropColumn('duplicated_by');
    });
}
};
