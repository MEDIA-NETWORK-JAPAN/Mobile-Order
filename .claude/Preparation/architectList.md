# アーキテクチャリスト

## プロジェクト概要
PHP 8.1+、MySQLデータベース、Viteアセットバンドリングを使用したLaravel 10モバイル注文アプリケーション。

## コアアーキテクチャコンポーネント

### バックエンドフレームワーク
- **Laravel 10**: メインのPHPフレームワーク
- **PHP 8.1+**: サーバーサイド言語
- **MySQL**: プライマリデータベース
- **Laravel Sanctum**: API認証

### フロントエンド・アセット
- **Vite**: アセットバンドリングとビルドツール
- **Blade**: ビュー用テンプレートエンジン
- **CSS/JS**: `resources/`にあるフロントエンドアセット

### 開発環境
- **Laravel Sail**: Dockerベースの開発環境
- **Docker Compose**: コンテナオーケストレーション

## ディレクトリ構造

### アプリケーション層 (`app/`)
- **Controllers** (`app/Http/Controllers/`): HTTPリクエスト処理
  - `Controller.php`: ベースコントローラークラス
- **Models** (`app/Models/`): Eloquent ORMモデル
  - `User.php`: ユーザーモデル
- **Middleware** (`app/Http/Middleware/`): リクエストフィルタリング
  - 認証、CSRF保護、暗号化など
- **Providers** (`app/Providers/`): サービス初期化
  - App、Auth、Broadcast、Event、Routeサービスプロバイダー
- **Console** (`app/Console/`): Artisanコマンド
- **Exceptions** (`app/Exceptions/`): 例外処理

### 設定 (`config/`)
- データベース、認証、キャッシュ、CORS、セッション設定
- 環境固有の設定

### データベース層 (`database/`)
- **Migrations** (`database/migrations/`): スキーマ定義
  - ユーザー、パスワードリセット、失敗ジョブ、個人アクセストークン
- **Factories** (`database/factories/`): テストデータ生成
- **Seeders** (`database/seeders/`): データベースシーディング

### ビュー・リソース (`resources/`)
- **Views** (`resources/views/`): Bladeテンプレート
- **CSS** (`resources/css/`): スタイルシート
- **JS** (`resources/js/`): JavaScriptファイル

### ルーティング (`routes/`)
- **web.php**: Webルート
- **api.php**: APIルート（`/api`プレフィックス付き）
- **channels.php**: ブロードキャストチャンネル
- **console.php**: コンソールコマンド

### テスト (`tests/`)
- **Feature**: 統合テスト
- **Unit**: ユニットテスト
- PHPUnit設定

### パブリックアセット (`public/`)
- エントリーポイント (`index.php`)
- 静的アセット (favicon、robots.txt)

### ストレージ (`storage/`)
- アプリケーションファイル、フレームワークキャッシュ、ログ、セッション

## 主要技術・ライブラリ

### バックエンド依存関係
- Laravel Framework 10.x
- Laravel Sanctum (API認証)
- PHP 8.1+機能

### フロントエンド依存関係
- Vite (ビルドツール)
- Laravel Vite Plugin

### 開発ツール
- Laravel Sail (Docker)
- PHPUnit (テスト)
- Laravel Pint (コードスタイル)

## データベーススキーマ
現在のテーブル:
- `users`: ユーザー認証とプロファイル
- `password_reset_tokens`: パスワードリセット機能
- `failed_jobs`: キュージョブ失敗追跡
- `personal_access_tokens`: APIトークン管理 (Sanctum)

## 認証・セキュリティ
- API認証用Laravel Sanctum
- CSRF保護ミドルウェア
- Cookie暗号化
- リクエスト検証
- 信頼できるホスト/プロキシ設定

## 開発ワークフロー
1. Laravel SailによるDockerベース開発
2. アセットコンパイルとホットリロード用Vite
3. PHPUnitによるテスト（FeatureとUnitテスト）
4. Laravel Pintによるコードスタイル強制
5. 各種操作用Artisanコマンド

## デプロイメント考慮事項
- `npm run build`でのアセットコンパイル
- 本番環境用設定キャッシュ
- ルートとビューキャッシュ最適化
- バックグラウンドジョブ用キューワーカー設定