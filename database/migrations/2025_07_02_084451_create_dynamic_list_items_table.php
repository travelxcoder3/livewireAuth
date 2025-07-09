<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create dynamic_list_items table.
     *
     * This table stores the main items that belong to dynamic lists.
     * Each item can be either a system item or an agency-specific item.
     */
    public function up(): void
    {
        Schema::create('dynamic_list_items', function (Blueprint $table) {
            // Primary key
            $table->id()->comment('Unique identifier for the list item');

            // Foreign key to dynamic_lists table
            $table->foreignId('dynamic_list_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('Reference to the parent dynamic list');

            // System flag
            $table->boolean('is_system')
                ->default(false)
                ->comment('Whether this is a system item (true) or custom item (false)');

            // Item label
            $table->string('label')
                ->comment('Display label/text for the list item');

            // Sorting order
            $table->integer('order')
                ->default(0)
                ->comment('Position of the item in the list (for sorting)');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('dynamic_list_id');
            $table->index('order');
            $table->index('is_system');

            // Add composite index for better performance on common queries
            $table->index(['dynamic_list_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the dynamic_list_items table and all its data.
     * Note: This will automatically drop any foreign key constraints.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_list_items');
    }
};
