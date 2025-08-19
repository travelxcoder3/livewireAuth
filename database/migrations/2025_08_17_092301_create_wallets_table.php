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
    Schema::create('wallets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id')->constrained()->onDelete('cascade');
        // العملة تُؤخذ من agencies عبر علاقة العميل، لذا لا عمود للعملة هنا
        $table->decimal('balance', 14, 2)->default(0);
        $table->enum('status', ['active','frozen'])->default('active');
        $table->timestamps();

        $table->unique('customer_id'); // محفظة واحدة لكل عميل
        $table->index('status');
    });
}

public function down(): void
{
    Schema::dropIfExists('wallets');
}

};
