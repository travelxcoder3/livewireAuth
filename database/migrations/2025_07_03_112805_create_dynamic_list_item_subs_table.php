<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create the dynamic_list_item_subs table.
     *
     * This table stores sub-items (البنود الفرعية) التي تنتمي إلى بند رئيسي واحد.
     * وتكون مملوكة بالكامل لوكالة محددة.
     */
    public function up(): void
    {
        Schema::create('dynamic_list_item_subs', function (Blueprint $table) {
            $table->id()
                ->comment('معرّف البند الفرعي');

            $table->foreignId('dynamic_list_item_id')
                ->constrained('dynamic_list_items')
                ->onDelete('cascade')
                ->comment('يرتبط بالبند الرئيسي');

            $table->foreignId('created_by_agency')
                ->constrained('agencies')
                ->onDelete('cascade')
                ->comment('الوكالة التي أنشأت هذا البند الفرعي');

            $table->string('label')
                ->comment('نص البند الفرعي الظاهر');

            $table->unsignedSmallInteger('order')
                ->default(0)
                ->comment('ترتيب البند الفرعي داخل البند الرئيسي');

            $table->timestamps();

            $table->index('dynamic_list_item_id');
            $table->index('created_by_agency');
            $table->index('order');
            $table->index(['dynamic_list_item_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_list_item_subs');
    }
};
