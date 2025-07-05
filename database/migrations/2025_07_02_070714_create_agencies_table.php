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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->text('address');
            $table->string('license_number')->unique();
            $table->string('commercial_record')->unique();
            $table->string('tax_number')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->date('license_expiry_date');
             $table->string('currency')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
