<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable(); // airline, hotel, car rental...
            $table->json('contact_info')->nullable(); // يمكن استخدامه مستقبلاً
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
