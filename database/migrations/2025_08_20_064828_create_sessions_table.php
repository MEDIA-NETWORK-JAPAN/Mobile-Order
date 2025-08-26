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
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 150)->unique()->comment('セッションID (暗号化セキュリティ強化版、POS端末のみ生成)');
            $table->unsignedBigInteger('store_id')->comment('店舗ID');
            $table->string('table_number', 50)->comment('テーブル番号');
            $table->unsignedInteger('customer_count')->nullable()->comment('利用人数（未設定時はNULL）');
            $table->enum('status', ['active', 'expired', 'completed'])->default('active')->comment('ステータス');
            $table->timestamp('expires_at')->nullable()->comment('有効期限（固定QRモード時はNULL）');
            $table->timestamp('started_at')->nullable()->comment('開始日時');
            $table->timestamp('completed_at')->nullable()->comment('完了日時');
            $table->timestamps();

            // ユニークキー
            $table->unique('session_id', 'uk_sessions_session_id');

            // インデックス
            $table->index('store_id', 'idx_sessions_store_id');
            $table->index('table_number', 'idx_sessions_table_number');
            $table->index('status', 'idx_sessions_status');
            $table->index('expires_at', 'idx_sessions_expires_at');

            // 外部キー制約
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('restrict');

            // テーブルコメント
            $table->comment('セッション管理（全てPOS端末で生成）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
