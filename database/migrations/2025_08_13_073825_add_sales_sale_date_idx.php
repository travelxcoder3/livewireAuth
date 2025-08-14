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
        Schema::table('sales', function (Blueprint $t) {
            // يدعم whereBetween/>=/<= على sale_date داخل الوكالة
            if (!$this->idxExists('sales','sales_agency_sale_date_id_idx')) {
                $t->index(['agency_id','sale_date','id'],'sales_agency_sale_date_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', fn(Blueprint $t) => $t->dropIndex('sales_agency_sale_date_id_idx'));
    }
};
