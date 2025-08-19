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
       Schema::create('commission_employee_rate_overrides', function (Blueprint $t) {
            $t->id();
            $t->foreignId('profile_id')->constrained('commission_profiles')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // بدل employee_id
            $t->decimal('rate', 8, 2);
            $t->timestamps();

            $t->unique(['profile_id','user_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_employee_rate_overrides');
    }
};
