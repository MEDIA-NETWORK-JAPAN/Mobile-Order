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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade')->comment('注文ID');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict')->comment('商品ID');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->integer('unit_price')->comment('単価（円）');
            $table->integer('total_price')->comment('小計（円）');
            $table->text('memo')->nullable()->comment('備考メモ');
            $table->timestamps();
            
            // インデックス
            $table->index('order_id', 'idx_order_items_order_id');
            $table->index('product_id', 'idx_order_items_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
