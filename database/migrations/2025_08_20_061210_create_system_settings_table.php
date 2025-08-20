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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->nullable()->comment('店舗ID（NULL=全体設定）');
            $table->string('key', 100)->comment('設定キー');
            $table->json('value')->nullable()->comment('設定値（JSON）');
            $table->text('description')->nullable()->comment('設定説明');
            $table->boolean('is_public')->default(false)->comment('公開設定フラグ');
            $table->timestamps();
            
            // 複合ユニークキー（店舗ID + 設定キー）
            $table->unique(['store_id', 'key'], 'uk_system_settings_store_key');
            
            // インデックス
            $table->index('key', 'idx_system_settings_key');
            
            // 外部キー制約
            $table->foreign('store_id')
                  ->references('id')
                  ->on('stores')
                  ->onDelete('cascade');
            
            // テーブルコメント
            $table->comment('システム設定');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
