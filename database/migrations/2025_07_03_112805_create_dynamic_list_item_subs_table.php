<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create the dynamic_list_item_subs table.
     *
     * This table stores sub-items that belong to main dynamic list items.
     * Used for hierarchical list structures where items have nested options.
     */
    public function up(): void
    {
        Schema::create('dynamic_list_item_subs', function (Blueprint $table) {
            // Primary Key
            $table->id()
                ->comment('Unique identifier for the sub-item');

            // Foreign Key Relationship
            $table->foreignId('dynamic_list_item_id')
                ->constrained('dynamic_list_items')
                ->onDelete('cascade')
                ->comment('Reference to the parent list item');

            // Content Field
            $table->string('label')
                ->comment('Display text for the sub-item');

            // Ordering Field
            $table->unsignedSmallInteger('order')
                ->default(0)
                ->comment('Sorting position within parent item');

            // System Flag
            $table->boolean('is_system')
                ->default(false)
                ->comment('Whether this is a system sub-item (true) or custom (false)');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('dynamic_list_item_id');
            $table->index('order');
            $table->index('is_system');

            // Composite index for common query patterns
            $table->index(['dynamic_list_item_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Safely drops the dynamic_list_item_subs table and all its constraints.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_list_item_subs');
    }
};
