<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('cascade');
            $table->date('month'); // بداية الشهر، مثل 2025-07-01
            $table->decimal('target_amount', 15, 2); // قيمة الهدف الشهري
            $table->timestamps();

            $table->unique(['agency_id', 'month']); // ضمان عدم تكرار نفس الشهر لنفس الوكالة
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_targets');
    }
};
