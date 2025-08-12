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
            if (!$this->idxExists('sales','sales_agency_sale_date_id_idx')) {
                $table->index(['agency_id','sale_date','id'],'sales_agency_sale_date_id_idx');
            }
            if (!$this->idxExists('sales','sales_agency_status_idx')) {
                $table->index(['agency_id','status'],'sales_agency_status_idx');
            }
            if (!$this->idxExists('sales','sales_agency_service_type_idx')) {
                $table->index(['agency_id','service_type_id'],'sales_agency_service_type_idx');
            }
            if (!$this->idxExists('sales','sales_agency_provider_idx')) {
                $table->index(['agency_id','provider_id'],'sales_agency_provider_idx');
            }
            if (!$this->idxExists('sales','sales_agency_customer_idx')) {
                $table->index(['agency_id','customer_id'],'sales_agency_customer_idx');
            }
            if (!$this->idxExists('sales','sales_agency_user_idx')) {
                $table->index(['agency_id','user_id'],'sales_agency_user_idx');
            }
            if (!$this->idxExists('sales','sales_agency_payment_method_idx')) {
                $table->index(['agency_id','payment_method'],'sales_agency_payment_method_idx');
            }
            if (!$this->idxExists('sales','sales_agency_payment_type_idx')) {
                $table->index(['agency_id','payment_type'],'sales_agency_payment_type_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_agency_sale_date_id_idx');
            $table->dropIndex('sales_agency_status_idx');
            $table->dropIndex('sales_agency_service_type_idx');
            $table->dropIndex('sales_agency_provider_idx');
            $table->dropIndex('sales_agency_customer_idx');
            $table->dropIndex('sales_agency_user_idx');
            $table->dropIndex('sales_agency_payment_method_idx');
            $table->dropIndex('sales_agency_payment_type_idx');
        });
    }
};
