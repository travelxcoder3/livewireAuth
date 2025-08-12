<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function idxExists($t,$i){
        $r = DB::selectOne(
            'SELECT COUNT(1) c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$t,$i]
        );
        return (int)($r->c ?? 0) > 0;
    }

    public function up(): void
    {
        // للفرز على created_at مع agency_id (يتوافق مع sortField الحالي)
        Schema::table('sales', function (Blueprint $t) {
            if (!$this->idxExists('sales','sales_agency_created_at_id_idx')) {
                $t->index(['agency_id','created_at','id'],'sales_agency_created_at_id_idx');
            }
        });

        // لتسريع whereHas(user name like ...) داخل نفس الوكالة
        Schema::table('users', function (Blueprint $t) {
            if (!$this->idxExists('users','users_agency_name_idx')) {
                $t->index(['agency_id','name'],'users_agency_name_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', fn(Blueprint $t) => $t->dropIndex('sales_agency_created_at_id_idx'));
        Schema::table('users', fn(Blueprint $t) => $t->dropIndex('users_agency_name_idx'));
    }
};