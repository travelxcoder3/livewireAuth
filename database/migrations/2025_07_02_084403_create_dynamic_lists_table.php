<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create dynamic_lists table.
     *
     * This table stores the main dynamic lists that can be:
     * - System lists (created by admin)
     * - Agency-specific lists (created by agencies)
     * - Copies of system lists customized by agencies
     */
    public function up(): void
    {
        Schema::create('dynamic_lists', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to agencies table (nullable for system lists)
            $table->foreignId('agency_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade')
                ->comment('Null for system lists, agency_id for agency-specific lists');

            // Reference to original list (for cloned/copied lists)
            $table->unsignedBigInteger('original_id')
                ->nullable()
                ->comment('Original system list ID for agency copies');

            // Flag for system lists
            $table->boolean('is_system')
                ->default(false)
                ->comment('Whether this is a system list (true) or agency list (false)');

            // List name
            $table->string('name')
                ->comment('Display name of the list');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('agency_id');
            $table->index('original_id');
            $table->index('is_system');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the dynamic_lists table after properly removing foreign key constraints.
     */
    public function down(): void
    {
        Schema::table('dynamic_lists', function (Blueprint $table) {
            // First drop the foreign key constraint
            $table->dropForeign(['agency_id']);

            // Then drop the column
            $table->dropColumn('agency_id');
        });

        // Finally drop the entire table
        Schema::dropIfExists('dynamic_lists');
    }
};
