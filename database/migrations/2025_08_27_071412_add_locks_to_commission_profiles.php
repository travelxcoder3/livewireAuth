<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('commission_profiles', function (Blueprint $t) {
            if (!Schema::hasColumn('commission_profiles','employee_rate_locked')) {
                $t->boolean('employee_rate_locked')->default(false)->after('employee_rate');
            }
            if (!Schema::hasColumn('commission_profiles','collector_baselines_locked')) {
                $t->boolean('collector_baselines_locked')->default(false)->after('employee_rate_locked');
            }
        });
    }
    public function down(): void {
        Schema::table('commission_profiles', function (Blueprint $t) {
            if (Schema::hasColumn('commission_profiles','employee_rate_locked')) {
                $t->dropColumn('employee_rate_locked');
            }
            if (Schema::hasColumn('commission_profiles','collector_baselines_locked')) {
                $t->dropColumn('collector_baselines_locked');
            }
        });
    }
};