<?php
// database/migrations/2025_08_12_010000_alter_quotation_number_nullable.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('quotations', function (Blueprint $t) {
            $t->string('quotation_number')->nullable()->change(); // UNIQUE يبقى كما هو. MySQL يسمح بعدة NULL.
        });
    }
    public function down(): void {
        Schema::table('quotations', function (Blueprint $t) {
            $t->string('quotation_number')->nullable(false)->change();
        });
    }
};
