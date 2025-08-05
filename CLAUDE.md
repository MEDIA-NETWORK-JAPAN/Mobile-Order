# CLAUDE.md

このファイルは、このリポジトリでコードを操作する際にClaude Code (claude.ai/code) にガイダンスを提供します。

## プロジェクト概要

これは「mobile-order」と呼ばれるLaravel 10アプリケーションで、PHPで構築されたWebアプリケーションフレームワークです。アセットコンパイル用のViteで構築されたフロントエンドと、API認証用のLaravel Sanctumが含まれています。

## 開発コマンド

**Sailエイリアス設定:**
`sail`コマンドを簡単に使用するため、以下のエイリアスを設定済み:
```bash
# ~/.bashrcに追加済み
sail() {
    if [[ -f "./vendor/bin/sail" ]]; then
        ./vendor/bin/sail "$@"
    else
        echo "Laravel Sailが見つかりません。プロジェクトディレクトリにいることを確認してください。"
    fi
}
```

**Laravel Sailコマンド（Docker環境）:**
- `sail up -d` - Sailコンテナをバックグラウンドで起動
- `sail down` - Sailコンテナを停止
- `sail ps` - 実行中のコンテナ状態を確認
- `sail artisan migrate` - データベースマイグレーションを実行
- `sail artisan migrate:fresh` - 全テーブルを削除して再マイグレーション
- `sail artisan tinker` - Laravelの対話シェルを開く
- `sail artisan make:controller ControllerName` - 新しいコントローラーを作成
- `sail artisan make:model ModelName` - 新しいモデルを作成
- `sail artisan make:migration migration_name` - 新しいマイグレーションを作成
- `sail composer install` - PHP依存関係をインストール
- `sail composer dump-autoload` - オートローダーを再生成

**フロントエンド/アセットコマンド（Sail環境）:**
- `sail npm install` - JavaScript依存関係をインストール
- `sail npm run dev` - ホットリロード付きVite開発サーバーを起動
- `sail npm run build` - 本番用アセットをビルド

**テスト（Sail環境）:**
- `sail artisan test` - 全テストを実行 (PHPUnit)
- `sail artisan test tests/Feature/ExampleTest.php` - 特定のテストファイルを実行

**コード品質（Sail環境）:**
- `sail exec laravel.test vendor/bin/pint` - Laravel Pintコードフォーマッターを実行

**その他のSailコマンド:**
- `sail exec laravel.test bash` - コンテナにアクセス
- `sail mysql` - MySQLクライアントにアクセス
- `sail redis` - Redisクライアントにアクセス

## アーキテクチャ

**バックエンド構造:**
- **Controllers** (`app/Http/Controllers/`) - HTTPリクエストとレスポンスを処理
- **Models** (`app/Models/`) - データベース操作用のEloquent ORMモデル
- **Middleware** (`app/Http/Middleware/`) - リクエストのフィルタリングと処理
- **Providers** (`app/Providers/`) - サービスコンテナのバインディングとブートストラップ
- **Routes** (`routes/`) - アプリケーションルート定義 (web.php, api.php)
- **Migrations** (`database/migrations/`) - データベーススキーマのバージョン管理
- **Factories** (`database/factories/`) - テスト/シーディング用のモデルファクトリ

**フロントエンド構造:**
- **Assets** (`resources/`) - CSS、JS、Bladeビューテンプレート
- **Public** (`public/`) - Webサーバーのドキュメントルートとコンパイル済みアセット
- **Vite設定** - ホットリロード付きモダンフロントエンドビルドツール

**設定:**
- **Environment** - 環境固有の設定には`.env`ファイルを使用
- **Config Files** (`config/`) - アプリケーション設定モジュール
- **Storage** (`storage/`) - ファイルストレージ、ログ、キャッシュ

## Laravel の主要概念

## Docker環境詳細

**構成サービス:**
- **Laravel アプリケーション** - PHP 8.1、ポート 8080でアクセス
- **MySQL 8.0** - データベース、ポート 3306
- **Redis** - キャッシュ・セッション、ポート 6379
- **Mailpit** - メール送信テスト、Web UI: http://localhost:8025

**アクセスURL:**
- アプリケーション: http://localhost:8080
- Mailpit管理画面: http://localhost:8025
- Vite開発サーバー: http://localhost:5173

## Laravel の主要概念

このプロジェクトは以下のLaravelのMVCアーキテクチャに従っています:
- **Eloquent ORM** - データベース操作用
- **Blade templating** - ビュー用
- **Artisan CLI** - 開発タスク用
- **Service container** - 依存性注入用
- **Sanctum** - API認証用
- **Laravel Sail** - Docker開発環境