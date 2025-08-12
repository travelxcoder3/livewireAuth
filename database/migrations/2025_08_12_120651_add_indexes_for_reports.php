<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function idxExists($t,$i){$r=DB::selectOne(
        'SELECT COUNT(1) c FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?',[$t,$i]);return (int)($r->c??0)>0;}

    public function up(): void {
        Schema::table('sales', function (Blueprint $table) {
            if(!$this->idxExists('sales','sales_agency_pnr_idx')){
                $table->index(['agency_id','pnr'],'sales_agency_pnr_idx');
            }
        });
    }
    public function down(): void {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_agency_pnr_idx');
        });
    }
};