<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dynamic_list_items', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('order');
        });
    }

    public function down()
    {
        Schema::table('dynamic_list_items', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
}; 