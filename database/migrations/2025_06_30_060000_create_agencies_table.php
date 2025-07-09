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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الوكالة
            $table->string('main_branch_name'); // اسم الفرع الرئيسي
            $table->string('email')->unique(); // البريد الإلكتروني
            $table->string('phone')->nullable(); // رقم الهاتف
            $table->string('landline')->nullable(); // الهاتف الثابت
            $table->string('logo')->nullable(); // الشعار
            $table->string('currency'); // العملة
            $table->text('address')->nullable(); // العنوان
            $table->string('license_number')->unique(); // رقم الرخصة
            $table->string('commercial_record')->unique(); // السجل التجاري
            $table->string('tax_number')->unique(); // الرقم الضريبي
            $table->text('description')->nullable(); // وصف
            $table->integer('max_users')->unsigned()->default(10);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active'); // الحالة
            $table->date('license_expiry_date'); // تاريخ انتهاء الرخصة
            $table->date('subscription_start_date')->nullable(); 
            $table->date('subscription_end_date')->nullable();   
            $table->string('theme_color')->default('emerald');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
