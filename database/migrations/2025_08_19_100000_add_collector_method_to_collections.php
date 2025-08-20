<?php
// database/migrations/2025_08_19_100000_add_collector_method_to_collections.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('collections', function (Blueprint $table) {
            if (!Schema::hasColumn('collections', 'collector_method')) {
                $table->unsignedTinyInteger('collector_method')->nullable()->after('method');
                $table->index('collector_method');
            }
        });
    }
    public function down(): void {
        Schema::table('collections', function (Blueprint $table) {
            if (Schema::hasColumn('collections', 'collector_method')) {
                $table->dropIndex(['collector_method']);
                $table->dropColumn('collector_method');
            }
        });
    }
};
