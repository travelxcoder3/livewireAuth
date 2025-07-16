<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade'); // يربط المستفيد بوكالة معينة
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'phone_number']); // لضمان عدم تكرار نفس الرقم داخل نفس الوكالة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
