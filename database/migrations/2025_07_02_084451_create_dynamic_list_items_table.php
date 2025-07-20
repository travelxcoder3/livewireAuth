<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create dynamic_list_items table.
     *
     * This table stores the main items (البنود الرئيسية) التي تنتمي إلى قائمة ديناميكية معينة.
     * كل بند يكون مملوكًا لوكالة محددة فقط.
     */
    public function up(): void
    {
        Schema::create('dynamic_list_items', function (Blueprint $table) {
            $table->id()->comment('معرّف البند الرئيسي');

            $table->foreignId('dynamic_list_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('يرتبط بالقائمة الديناميكية');

            $table->foreignId('created_by_agency')
                ->constrained('agencies')
                ->onDelete('cascade')
                ->comment('الوكالة التي أنشأت هذا البند');

            $table->string('label')
                ->comment('نص البند الظاهر');

            $table->integer('order')
                ->default(0)
                ->comment('ترتيب البند في القائمة');

            $table->timestamps();

            $table->index('dynamic_list_id');
            $table->index('created_by_agency');
            $table->index('order');
            $table->index(['dynamic_list_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_list_items');
    }
};
