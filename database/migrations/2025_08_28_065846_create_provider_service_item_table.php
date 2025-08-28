<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_service_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_item_id')->constrained('dynamic_list_items')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['provider_id','service_item_id']);
            $table->index('service_item_id');
        });

        // ترحيل البيانات من العمود القديم للجدول الوسيط
        DB::statement("
            INSERT INTO provider_service_item (provider_id, service_item_id, created_at, updated_at)
            SELECT id AS provider_id, service_item_id, NOW(), NOW()
            FROM providers
            WHERE service_item_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_service_item');
    }
};
