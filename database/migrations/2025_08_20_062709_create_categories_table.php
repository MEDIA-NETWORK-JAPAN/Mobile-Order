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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->comment('店舗ID');
            $table->string('name', 255)->comment('カテゴリ名');
            $table->json('translations')->nullable()->comment('多言語翻訳（JSON）');
            $table->integer('sort_order')->default(0)->comment('ソート順');
            $table->boolean('is_active')->default(true)->comment('アクティブフラグ');
            $table->timestamps();

            // インデックス
            $table->index('store_id', 'idx_categories_store_id');
            $table->index('sort_order', 'idx_categories_sort_order');
            $table->index('is_active', 'idx_categories_is_active');

            // 外部キー制約
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('restrict');

            // テーブルコメント
            $table->comment('商品カテゴリマスター');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
