<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \DB::statement('ALTER TABLE providers MODIFY contact_info VARCHAR(20) NULL;');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE providers MODIFY contact_info JSON NULL;');
    }
}; 