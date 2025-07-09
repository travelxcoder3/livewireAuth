<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intermediary_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intermediary_id')->constrained()->onDelete('cascade');
            $table->string('type');   // whatsapp, phone, email
            $table->string('value');  // الرقم أو البريد
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intermediary_contacts');
    }
};
