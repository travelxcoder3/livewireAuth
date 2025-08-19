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
        Schema::create('commission_collector_overrides', function (Blueprint $t) {
                $t->id();
                $t->foreignId('profile_id')->constrained('commission_profiles')->cascadeOnDelete();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // بدل employee_id
                $t->unsignedTinyInteger('method');
                $t->enum('type', ['percent','fixed']);
                $t->decimal('value', 12, 2);
                $t->enum('basis', ['collected_amount','net_margin','employee_commission']);
                $t->timestamps();

                $t->unique(['profile_id','user_id','method']);
                $t->index(['profile_id']);
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_collector_overrides');
    }
};
