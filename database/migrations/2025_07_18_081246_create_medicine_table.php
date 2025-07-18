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
        Schema::create('medicine', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('license_number')->unique()->comment('許可證字號');
            $table->string('chinese_name')->nullable()->comment('中文品名');
            $table->string('english_name')->nullable()->comment('英文品名');
            $table->timestamps();

            $table->comment('藥品資料');
        });

        Schema::create('medicine_appearances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('attr_key')->index()->comment('特徵key');
            $table->string('attr_value')->index()->comment('特徵value');
            $table->unique(['attr_key', 'attr_value']);
            $table->comment('藥品特徵');
        });

        // 特徵關聯表
        Schema::create('medicine_appearance_medicine', function (Blueprint $table) {
            $table->unsignedBigInteger('medicine_id')->index()->comment('藥品資料_ID');
            $table->unsignedBigInteger('medicine_appearance_id')->index()->comment('藥品特徵_ID');

            $table->unique(
                ['medicine_id', 'medicine_appearance_id'],
                'medicine_appearance_medicine_unique'
            );

            $table->foreign('medicine_id')
                ->references('id')->on('medicine')->onDelete('cascade');

            $table->foreign('medicine_appearance_id')
                ->references('id')->on('medicine_appearances')->onDelete('cascade');

            $table->comment('藥品資料_藥品特徵_關聯表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_appearance_medicine');
        Schema::dropIfExists('medicine_appearances');
        Schema::dropIfExists('medicine');
    }
};
