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
        Schema::create('pos_health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade')->comment('店舗ID');
            $table->timestamp('last_check_at')->comment('最終チェック日時');
            $table->enum('status', ['online', 'warning', 'error', 'syncing'])->default('online')->comment('POSステータス');
            $table->unsignedInteger('consecutive_success')->default(0)->comment('連続成功回数');
            $table->unsignedInteger('timeout_threshold_seconds')->default(30)->comment('タイムアウト閾値（秒）');
            $table->unsignedInteger('error_threshold_seconds')->default(90)->comment('エラー閾値（秒）');
            $table->timestamps();
            
            // インデックス
            $table->unique('store_id', 'uk_pos_health_checks_store');
            $table->index('status', 'idx_pos_health_checks_status');
            $table->index('last_check_at', 'idx_pos_health_checks_last_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_health_checks');
    }
};
