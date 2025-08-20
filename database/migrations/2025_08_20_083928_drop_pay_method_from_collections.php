<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            if (Schema::hasColumn('collections', 'pay_method')) {
                $table->dropColumn('pay_method');
            }
        });
    }
    public function down()
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->string('pay_method')->nullable();
        });
    }

};
