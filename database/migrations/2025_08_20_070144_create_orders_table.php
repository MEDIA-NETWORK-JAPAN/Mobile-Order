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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict')->comment('店舗ID');
            $table->foreignId('session_id')->constrained('sessions')->onDelete('restrict')->comment('セッションID（席での注文履歴共有用）');
            $table->string('guest_token')->comment('ゲストトークン（個人識別・不正防止用）');
            $table->string('device_fingerprint')->comment('デバイス識別（不正アクセス排除用）');
            $table->string('order_number', 50)->unique()->comment('注文番号');
            $table->enum('status', ['pending', 'preparing', 'completed', 'cancelled'])->default('pending')->comment('注文ステータス');
            $table->integer('total_amount')->comment('合計金額（円）');
            $table->text('memo')->nullable()->comment('備考メモ');
            $table->timestamp('ordered_at')->comment('注文日時');
            $table->timestamp('confirmed_at')->nullable()->comment('確認日時');
            $table->timestamp('completed_at')->nullable()->comment('完了日時');
            $table->timestamps();
            
            // インデックス
            $table->index('store_id', 'idx_orders_store_id');
            $table->index('session_id', 'idx_orders_session_id');
            $table->index('guest_token', 'idx_orders_guest_token');
            $table->index('status', 'idx_orders_status');
            $table->index('ordered_at', 'idx_orders_ordered_at');
            $table->index('device_fingerprint', 'idx_orders_device_fingerprint');
            $table->index(['session_id', 'guest_token'], 'idx_orders_session_guest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
