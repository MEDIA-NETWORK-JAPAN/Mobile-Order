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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->comment('店舗ID');
            $table->string('code', 45)->comment('POS商品ID');
            $table->string('name', 255)->comment('商品名');
            $table->text('description')->nullable()->comment('商品説明');
            $table->integer('price')->comment('価格（税抜、円）');
            $table->integer('tax_in_price')->comment('税込価格（円）');
            $table->integer('cost')->nullable()->comment('原価（円）');
            $table->enum('tax_type', ['standard', 'reduced', 'exempt', 'non_taxable'])->comment('税区分');
            $table->enum('availability_status', ['available', 'sold_out', 'not_arrived', 'preparing'])
                  ->default('available')
                  ->comment('提供状態');
            $table->string('availability_message', 255)->nullable()->comment('提供状態メッセージ');
            $table->time('expected_available_time')->nullable()->comment('提供可能予定時刻');
            $table->json('translations')->nullable()->comment('多言語翻訳（JSON）');
            $table->string('image_url', 500)->nullable()->comment('メイン画像URL');
            $table->integer('sort_order')->default(0)->comment('ソート順');
            $table->boolean('is_active')->default(true)->comment('アクティブフラグ');
            $table->timestamps();
            $table->softDeletes();
            
            // 複合ユニークキー（店舗ID + POS商品ID）
            $table->unique(['store_id', 'code'], 'uk_products_store_code');
            
            // インデックス
            $table->index('store_id', 'idx_products_store_id');
            $table->index('availability_status', 'idx_products_availability_status');
            $table->index('is_active', 'idx_products_is_active');
            $table->index('sort_order', 'idx_products_sort_order');
            
            // 外部キー制約
            $table->foreign('store_id')
                  ->references('id')
                  ->on('stores')
                  ->onDelete('restrict');
            
            // テーブルコメント
            $table->comment('商品マスター');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
