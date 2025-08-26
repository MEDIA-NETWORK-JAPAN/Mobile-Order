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
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->unsignedBigInteger('category_id')->comment('カテゴリID');
            $table->integer('sort_order')->default(0)->comment('ソート順');
            $table->timestamps();

            // インデックス
            $table->index('product_id', 'idx_category_product_product_id');
            $table->index('category_id', 'idx_category_product_category_id');
            $table->index('sort_order', 'idx_category_product_sort_order');

            // 外部キー制約
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');

            // テーブルコメント
            $table->comment('商品カテゴリ紐付け');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
