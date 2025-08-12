<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function idxExists($table, $index) {
        $row = DB::selectOne(
            'SELECT COUNT(1) cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index]
        );
        return (int)($row->cnt ?? 0) > 0;
    }

    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!$this->idxExists('sales','sales_agency_service_date_id_idx')) {
                $table->index(['agency_id','service_date','id'], 'sales_agency_service_date_id_idx');
            }
            if (!$this->idxExists('sales','sales_agency_reference_idx')) {
                $table->index(['agency_id','reference'], 'sales_agency_reference_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_agency_service_date_id_idx');
            $table->dropIndex('sales_agency_reference_idx');
        });
    }
};
