<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('commission_collector_rules', function (Blueprint $t) {
            $t->decimal('value', 12, 4)->nullable()->change();
        });

        Schema::table('commission_collector_overrides', function (Blueprint $t) {
            $t->decimal('value', 12, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('commission_collector_rules', function (Blueprint $t) {
            $t->decimal('value', 12, 2)->nullable()->change();
        });

        Schema::table('commission_collector_overrides', function (Blueprint $t) {
            $t->decimal('value', 12, 2)->change();
        });
    }
};
