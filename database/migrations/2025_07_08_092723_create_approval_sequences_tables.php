<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول التسلسلات
        Schema::create('approval_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('name'); // اسم التسلسل
            $table->string('action_type'); // نوع الإجراء: قبول، رفض، حذف، تعديل
            $table->timestamps();
        });

        // جدول الموظفين المرتبطين بالتسلسل
        Schema::create('approval_sequence_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_sequence_id')->constrained('approval_sequences')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('step_order'); // ترتيب الموافقة
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_sequence_users');
        Schema::dropIfExists('approval_sequences');
    }
};
