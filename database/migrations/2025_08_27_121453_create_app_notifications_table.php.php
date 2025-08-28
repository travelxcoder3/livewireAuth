<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('app_notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agency_id')->nullable()->index();
            $t->unsignedBigInteger('user_id')->index();           // المستلم
            $t->string('type', 50)->nullable();                   // اختياري (sale_edit…)
            $t->string('title', 200);
            $t->text('body')->nullable();
            $t->string('url', 500)->nullable();
            $t->boolean('is_read')->default(false)->index();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['user_id','is_read','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('app_notifications'); }
};
