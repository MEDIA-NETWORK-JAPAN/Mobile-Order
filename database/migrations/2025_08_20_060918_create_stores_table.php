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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('店舗コード（URL識別用）');
            $table->string('name', 255)->comment('店舗名');
            $table->text('description')->nullable()->comment('店舗説明');
            $table->string('phone', 20)->nullable()->comment('電話番号');
            $table->string('email', 255)->nullable()->comment('メールアドレス');
            $table->text('address')->nullable()->comment('住所');
            $table->json('business_hours')->nullable()->comment('営業時間（JSON）');
            $table->enum('qr_mode', ['fixed', 'temporary'])->default('temporary')->comment('QRコード運用モード');
            $table->json('settings')->nullable()->comment('店舗設定（JSON）');
            $table->boolean('is_active')->default(true)->comment('アクティブフラグ');
            $table->timestamps();
            $table->softDeletes();
            
            // インデックス
            $table->index('code', 'idx_stores_code');
            $table->index('is_active', 'idx_stores_is_active');
            
            // テーブルコメント
            $table->comment('店舗');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
