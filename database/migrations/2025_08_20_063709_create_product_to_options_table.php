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
        Schema::create('product_to_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->unsignedBigInteger('option_id')->comment('オプションID');
            $table->integer('sort_order')->default(0)->comment('ソート順');
            $table->timestamps();
            
            // インデックス
            $table->index('product_id', 'idx_product_to_options_product_id');
            $table->index('option_id', 'idx_product_to_options_option_id');
            
            // 外部キー制約
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
            $table->foreign('option_id')
                  ->references('id')
                  ->on('options')
                  ->onDelete('cascade');
            
            // テーブルコメント
            $table->comment('商品とオプションの紐付け');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_to_options');
    }
};
