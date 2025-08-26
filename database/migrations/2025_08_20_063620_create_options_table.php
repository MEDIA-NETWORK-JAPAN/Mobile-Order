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
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->comment('店舗ID');
            $table->string('title', 45)->comment('オプションタイトル');
            $table->boolean('required')->default(false)->comment('必須フラグ');
            $table->enum('selection_type', ['single', 'multiple'])->default('single')->comment('選択タイプ');
            $table->json('translations')->nullable()->comment('多言語翻訳（JSON）');
            $table->timestamps();

            // インデックス
            $table->index('store_id', 'idx_options_store_id');

            // 外部キー制約
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('restrict');

            // テーブルコメント
            $table->comment('商品オプションマスター');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
