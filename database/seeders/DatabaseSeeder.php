<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * アプリケーションのデータベースにデータを投入
     */
    public function run(): void
    {
        // 環境に応じてシーダーを実行
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                TestDataSeeder::class,
            ]);

            $this->command->info('開発・テスト環境用のデータを投入しました。');
        } else {
            $this->command->info('本番環境では手動でデータを管理してください。');
        }
    }
}
