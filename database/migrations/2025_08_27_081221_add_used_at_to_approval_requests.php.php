<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('approval_requests', function (Blueprint $t) {
            if (!Schema::hasColumn('approval_requests','used_at')) {
                $t->timestamp('used_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void {
        Schema::table('approval_requests', function (Blueprint $t) {
            if (Schema::hasColumn('approval_requests','used_at')) {
                $t->dropColumn('used_at');
            }
        });
    }
};
