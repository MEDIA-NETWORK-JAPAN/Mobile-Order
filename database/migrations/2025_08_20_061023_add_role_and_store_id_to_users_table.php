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
        Schema::table('users', function (Blueprint $table) {
            // role カラムを追加
            $table->enum('role', ['super_admin', 'admin', 'staff', 'pos_system'])
                  ->default('staff')
                  ->after('password')
                  ->comment('ユーザー役割');
            
            // store_id カラムを追加
            $table->unsignedBigInteger('store_id')
                  ->nullable()
                  ->after('role')
                  ->comment('所属店舗ID');
            
            // is_active カラムを追加
            $table->boolean('is_active')
                  ->default(true)
                  ->after('store_id')
                  ->comment('アクティブフラグ');
            
            // last_login_at カラムを追加
            $table->timestamp('last_login_at')
                  ->nullable()
                  ->after('is_active')
                  ->comment('最終ログイン日時');
            
            // ソフトデリート用カラムを追加
            $table->softDeletes();
            
            // インデックスを追加
            $table->index('role', 'idx_users_role');
            $table->index('store_id', 'idx_users_store_id');
            
            // 外部キー制約を追加
            $table->foreign('store_id')
                  ->references('id')
                  ->on('stores')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 外部キー制約を削除
            $table->dropForeign(['store_id']);
            
            // インデックスを削除
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_store_id');
            
            // カラムを削除
            $table->dropColumn('role');
            $table->dropColumn('store_id');
            $table->dropColumn('is_active');
            $table->dropColumn('last_login_at');
            $table->dropSoftDeletes();
        });
    }
};
