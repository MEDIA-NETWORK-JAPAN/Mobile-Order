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
        Schema::create('order_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade')->comment('注文明細ID');
            $table->foreignId('option_id')->constrained('options')->onDelete('restrict')->comment('オプションID');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict')->comment('オプション商品ID');
            $table->unsignedInteger('quantity')->default(1)->comment('数量');
            $table->string('option_name')->comment('オプション名（スナップショット）');
            $table->string('product_name')->comment('選択肢名（スナップショット）');
            $table->integer('unit_price')->comment('単価（スナップショット、円）');
            $table->integer('total_price')->comment('小計（数量×単価、円）');
            $table->timestamps();
            
            // インデックス
            $table->index('order_item_id', 'idx_order_item_options_order_item_id');
            $table->index('option_id', 'idx_order_item_options_option_id');
            $table->index('product_id', 'idx_order_item_options_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_options');
    }
};
