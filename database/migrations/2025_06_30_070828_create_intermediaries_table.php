<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intermediaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('name'); // اسم الوسيط
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intermediaries');
    }
};
