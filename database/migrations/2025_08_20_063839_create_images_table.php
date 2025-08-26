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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->comment('店舗ID');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->string('filename', 45)->comment('画像ファイル名');
            $table->integer('sort_order')->default(0)->comment('ソート順');
            $table->timestamps();

            // インデックス
            $table->index('product_id', 'idx_images_product_id');
            $table->index('sort_order', 'idx_images_sort_order');

            // 外部キー制約
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('restrict');

            // テーブルコメント
            $table->comment('商品画像マスター');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
