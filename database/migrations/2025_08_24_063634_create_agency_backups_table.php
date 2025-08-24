<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('agency_backups', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agency_id')->index();
            $t->string('filename');          // اسم ملف الـ zip
            $t->unsignedBigInteger('size');  // بالبايت
            $t->string('status')->default('done'); // done|failed
            $t->json('meta')->nullable();    // أي تفاصيل إضافية
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('agency_backups'); }
};
