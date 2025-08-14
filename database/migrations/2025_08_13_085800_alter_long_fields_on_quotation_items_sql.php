<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE quotation_items MODIFY description LONGTEXT NULL');
        DB::statement('ALTER TABLE quotation_items MODIFY route TEXT NULL');
        DB::statement('ALTER TABLE quotation_items MODIFY conditions TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE quotation_items MODIFY description VARCHAR(255) NULL');
        DB::statement('ALTER TABLE quotation_items MODIFY route VARCHAR(255) NULL');
        DB::statement('ALTER TABLE quotation_items MODIFY conditions VARCHAR(255) NULL');
    }
};
