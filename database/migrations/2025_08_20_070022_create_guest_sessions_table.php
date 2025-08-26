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
        Schema::create('guest_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade')->comment('席セッションID');
            $table->string('device_fingerprint')->comment('デバイス識別');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict')->comment('店舗ID');

            // Laravel Breeze Authenticatable必須カラム
            $table->string('email')->unique()->comment('ゲスト識別用仮想メールアドレス（device_fingerprint@guest.local）');
            $table->timestamp('email_verified_at')->nullable()->comment('メール認証日時（ゲストは常にNULL）');
            $table->string('password')->nullable()->comment('パスワード（ゲストは使用しないためNULL）');
            $table->string('remember_token', 100)->nullable()->comment('Remember Meトークン（ゲストは使用しない）');

            // ゲスト専用カラム
            $table->string('guest_token', 64)->unique()->comment('ゲスト識別トークン（認証後発行）');
            $table->char('language', 2)->default('ja')->comment('言語設定');
            $table->boolean('agreed_policy')->default(false)->comment('ポリシー同意状態');
            $table->timestamp('expires_at')->comment('セッション有効期限（30分TTL）');
            $table->timestamp('last_activity')->useCurrent()->comment('最終アクティビティ時刻');

            // 視覚的識別情報
            $table->string('identifier_icon', 10)->default('🐶')->comment('識別アイコン（絵文字）');
            $table->string('identifier_color', 7)->default('#FF6B6B')->comment('識別カラー（HEXコード）');

            $table->timestamps();

            // インデックス
            $table->unique(['session_id', 'device_fingerprint'], 'uk_guest_device');
            $table->index('expires_at', 'idx_guest_sessions_expires');
            $table->index('device_fingerprint', 'idx_guest_sessions_device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_sessions');
    }
};
