<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('collections', function (Blueprint $t) {
            if (!Schema::hasColumn('collections','collector_user_id')) {
                $t->foreignId('collector_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                $t->index('collector_user_id');
            }
        });
    }
    public function down(): void {
        Schema::table('collections', function (Blueprint $t) {
            if (Schema::hasColumn('collections','collector_user_id')) {
                $t->dropConstrainedForeignId('collector_user_id');
            }
        });
    }
};
