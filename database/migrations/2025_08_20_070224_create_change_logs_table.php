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
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100)->comment('エンティティタイプ');
            $table->unsignedBigInteger('entity_id')->comment('エンティティID');
            $table->enum('action', ['created', 'updated', 'deleted'])->comment('アクション');
            $table->json('changes')->nullable()->comment('変更内容（JSON）');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('変更ユーザーID');
            $table->string('user_type', 50)->nullable()->comment('ユーザータイプ');
            $table->string('ip_address', 45)->nullable()->comment('IPアドレス');
            $table->text('user_agent')->nullable()->comment('ユーザーエージェント');
            $table->boolean('is_synced')->default(false)->comment('POS同期済みフラグ');
            $table->timestamp('synced_at')->nullable()->comment('POS同期日時');
            $table->foreignId('synced_by')->nullable()->constrained('users')->onDelete('set null')->comment('POS同期ユーザーID');
            $table->timestamps();

            // インデックス
            $table->index(['entity_type', 'entity_id'], 'idx_change_logs_entity');
            $table->index('action', 'idx_change_logs_action');
            $table->index('is_synced', 'idx_change_logs_is_synced');
            $table->index('created_at', 'idx_change_logs_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_logs');
    }
};
