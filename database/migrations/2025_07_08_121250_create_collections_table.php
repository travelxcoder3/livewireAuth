<?php

// database/migrations/2025_07_08_000000_create_collections_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('cascade');

            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('method')->nullable();
            $table->text('note')->nullable();

            // روابط الخصائص من القوائم الديناميكية
            $table->foreignId('customer_type_id')->nullable()->constrained('dynamic_list_item_subs')->nullOnDelete();
            $table->foreignId('debt_type_id')->nullable()->constrained('dynamic_list_item_subs')->nullOnDelete();
            $table->foreignId('customer_response_id')->nullable()->constrained('dynamic_list_item_subs')->nullOnDelete();
            $table->foreignId('customer_relation_id')->nullable()->constrained('dynamic_list_item_subs')->nullOnDelete();
            $table->foreignId('delay_reason_id')->nullable()->constrained('dynamic_list_item_subs')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
