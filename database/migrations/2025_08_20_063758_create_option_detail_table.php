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
        Schema::create('option_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('option_id')->comment('オプションID');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->boolean('default_selected')->default(false)->comment('デフォルトフラグ（画面表示時に選択される）');
            $table->integer('sort_order')->default(0)->comment('ソート順');
            $table->timestamps();

            // インデックス
            $table->index('option_id', 'idx_option_detail_option_id');
            $table->index('product_id', 'idx_option_detail_product_id');

            // 外部キー制約
            $table->foreign('option_id')
                ->references('id')
                ->on('options')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            // テーブルコメント
            $table->comment('商品オプション詳細');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_detail');
    }
};
