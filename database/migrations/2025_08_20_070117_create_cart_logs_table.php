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
        Schema::create('cart_logs', function (Blueprint $table) {
            $table->id();
            $table->string('guest_token')->comment('ゲストトークン');
            $table->foreignId('session_id')->constrained('sessions')->onDelete('restrict')->comment('セッションID');
            $table->enum('action', ['add', 'remove', 'update', 'clear'])->comment('カート操作');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict')->comment('商品ID');
            $table->unsignedInteger('quantity')->comment('数量（削除時は削除した数を記録）');
            $table->integer('unit_price')->comment('操作時の単価（円）');
            $table->json('options')->nullable()->comment('選択オプション（シンプルな配列）');
            $table->integer('cart_total')->comment('操作後のカート合計金額（円）');
            $table->boolean('is_success')->default(true)->comment('操作成功フラグ');
            $table->string('error_code', 50)->nullable()->comment('エラーコード（失敗時のみ）');
            $table->string('error_message')->nullable()->comment('エラーメッセージ（失敗時のみ）');
            $table->string('device_fingerprint')->comment('デバイスフィンガープリント');
            $table->string('ip_address', 45)->nullable()->comment('IPアドレス');
            $table->text('user_agent')->nullable()->comment('ユーザーエージェント');
            $table->timestamps();

            // インデックス
            $table->index('guest_token', 'idx_cart_logs_guest_token');
            $table->index('session_id', 'idx_cart_logs_session_id');
            $table->index('product_id', 'idx_cart_logs_product_id');
            $table->index('is_success', 'idx_cart_logs_is_success');
            $table->index('created_at', 'idx_cart_logs_created_at');
            $table->index('action', 'idx_cart_logs_action');
            $table->index('device_fingerprint', 'idx_cart_logs_device_fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_logs');
    }
};
