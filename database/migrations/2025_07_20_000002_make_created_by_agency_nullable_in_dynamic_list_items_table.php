<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dynamic_list_items', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_agency')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('dynamic_list_items', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_agency')->nullable(false)->change();
        });
    }
}; 