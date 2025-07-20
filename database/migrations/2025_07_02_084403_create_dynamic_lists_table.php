<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations to create dynamic_lists table.
     *
     * This table stores:
     * - System lists (is_system = true) created by seeder and shown to all agencies
     * - Custom lists (is_system = false) created by super admin or requested by an agency
     */
    public function up(): void
    {
        Schema::create('dynamic_lists', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agency_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade')
                ->comment('Null = متاحة لجميع الوكالات (قائمة تنظيمية)، أو رقم وكالة واحدة مخصصة');

            $table->foreignId('requested_by_agency')
                ->nullable()
                ->constrained('agencies')
                ->onDelete('set null')
                ->comment('الوكالة التي طلبت إنشاء القائمة');
            $table->unsignedBigInteger('original_id')
                ->nullable()
                ->comment('ID of the original system list if this is a copy for an agency');

            $table->index('original_id');

            $table->boolean('is_system')
                ->default(false)
                ->comment('True = قائمة تنظيمية منشأة عبر Seeder');

            $table->boolean('is_requested')
                ->default(false)
                ->comment('True = تم طلب إنشاء هذه القائمة من قبل وكالة');

            $table->boolean('is_approved')
                ->nullable()
                ->comment('Null = لم يتم مراجعتها، True = تمت الموافقة، False = تم الرفض');

            $table->string('name')
                ->comment('اسم القائمة الظاهر');

            $table->text('request_reason')->nullable();

            $table->timestamps();

            $table->index('agency_id');
            $table->index('is_system');
            $table->index('is_requested');
            $table->index('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_lists');
    }
};
