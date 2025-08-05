# CLAUDE.md

このファイルは、このリポジトリでコードを操作する際にClaude Code (claude.ai/code) にガイダンスを提供します。

## プロジェクト概要

### プロジェクト名
Mobile Order System（mobile-order）

### システム目的
飲食店向けモバイル注文システム。お客様がQRコードをスキャンしてスマートフォンからメニューを閲覧・注文し、POSシステムと連携する汎用的なオーダーシステムです。

### 主な機能
1. **QRコード読み取りによる注文開始** - POSレジ発行のQRコードをスマホで読み取り
2. **メニュー表示・注文** - 画像中心のグリッド表示、オプション選択機能
3. **リアルタイム更新** - ポーリング方式による売り切れ状態の自動更新
4. **多言語対応** - 日本語、英語、繁体字、簡体字、韓国語
5. **POS連携** - REST API（Laravel Sanctum認証）による外部POSシステム連携

### 技術スタック
- **バックエンド**: Laravel 10.48.29 + PHP 8.1+
- **フロントエンド**: Laravel Livewire 3.6+ + Alpine.js + Mary UI v2.4+
- **データベース**: MySQL 8.0+ 
- **認証**: Laravel Breeze（管理者）+ Laravel Sanctum（API）
- **開発環境**: Laravel Sail (Docker) + Redis + Mailpit

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

**パッケージ管理:**
- `sail composer require package-name` - PHPパッケージ追加
- `sail composer update` - 依存関係更新
- `sail composer dump-autoload` - オートローダー再生成

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

## 作成済みドキュメント一覧

### デザインシステム（モバイルファースト設計）
**フォルダ: `.claude/02_design_system/`**

- **基本設計**: `00_basic_design.md`
  - 技術スタック統合（Laravel + Livewire + Mary UI + TailwindCSS）
  - モバイルファースト設計方針
  - 品質基準とガイドライン

- **デザイン原則**: `01_design_principles.md`
  - カラーシステム（アンバー系プライマリ色）
  - タイポグラフィ（システムフォント中心）
  - スペーシング（4px基本単位）
  - アクセシビリティ（WCAG 2.1 AA準拠）

- **コンポーネント設計**: `02_component_design.md`
  - Mary UI活用パターン
  - Atoms/Molecules/Organisms分類
  - Livewire統合実装例
  - モバイル最適化コンポーネント

- **アニメーションシステム**: `03_animation_system.md`
  - **機能的アニメーションのみ**（ユーザー要求通り）
  - フラッシュメッセージ、ローディング、モーダル遷移
  - パフォーマンス最適化（GPU活用）
  - アクセシビリティ対応（prefers-reduced-motion）

- **レイアウトシステム**: `04_layout_system.md`
  - レスポンシブブレークポイント
  - グリッドシステム（Flexboxベース）
  - モバイル特化ナビゲーション
  - タッチターゲット最適化（44px以上）

### ライブラリベストプラクティス
**フォルダ: `.claude/03_library_best_practices/`**

- **Laravel Sail**: `01_laravel_sail_best_practices.md`
  - Docker環境セットアップ・最適化
  - 開発ワークフロー（起動・テスト・デプロイ）
  - パフォーマンス調整とトラブルシューティング
  - CI/CD統合パターン

- **Mary UI**: `02_mary_ui_best_practices.md`
  - コンポーネント活用パターンと実装例
  - Livewire統合ベストプラクティス
  - カスタマイズとテーマ設定
  - パフォーマンス最適化とアクセシビリティ

- **Livewireテスト戦略**: `03_livewire_test_strategy.md`
  - 単体・統合・ブラウザテスト戦略
  - コンポーネント別テストパターン
  - イベント・状態管理テスト
  - パフォーマンス・エラーハンドリングテスト

- **REST API連携**: `04_rest_api_integration_patterns.md`
  - RESTful API設計原則
  - Laravel Sanctum認証パターン
  - 外部サービス連携（決済・通知）
  - セキュリティ・パフォーマンス最適化

## タスク別クイックリファレンスマップ

### デザインシステム・UI実装
- **デザインガイドライン確認**: `.claude/02_design_system/01_design_principles.md`
- **コンポーネント実装**: `.claude/02_design_system/02_component_design.md`
- **アニメーション実装**: `.claude/02_design_system/03_animation_system.md`
- **レスポンシブレイアウト**: `.claude/02_design_system/04_layout_system.md`

### 開発環境・ライブラリ活用
- **Sail環境問題解決**: `.claude/03_library_best_practices/01_laravel_sail_best_practices.md`
- **Mary UIコンポーネント**: `.claude/03_library_best_practices/02_mary_ui_best_practices.md`
- **Livewireテスト**: `.claude/03_library_best_practices/03_livewire_test_strategy.md`
- **API設計・実装**: `.claude/03_library_best_practices/04_rest_api_integration_patterns.md`

### 既存開発ドキュメント（参考用）
- **機能要件**: `.claude/00_project/01_mo_concept_requirements.md`
- **アーキテクチャ**: `.claude/01_development_docs/01_architecture_design.md`
- **データベース設計**: `.claude/01_development_docs/02_database_design.md`
- **認証・権限**: `.claude/01_development_docs/13_auth_authorization_design.md`

## 重要な実装方針

### デザインシステムの基本方針
- **モバイルファースト**: スマートフォン用途を最優先（320px〜）
- **機能的アニメーションのみ**: フラッシュメセージなど必要最小限
- **アクセシビリティ**: WCAG 2.1 AA準拠、44px以上のタッチターゲット
- **一貫性**: 全画面で統一されたUIパターン

### 技術スタック統合
- **Mary UI v2.4+**: TailwindCSSベースのLivewire専用コンポーネント
- **カスタムテーマ**: アンバー系プライマリ色、システムフォント
- **パフォーマンス**: GPU活用アニメーション、キャッシュ戦略
- **テスト**: Livewireコンポーネント包括テスト、Browser testing

### 開発フロー
- **Phase 0**: プロジェクト初期化とドキュメント整備 ✅ **完了**
- **Phase 1**: 基盤構築（データベース、認証システム）
- **Phase 2**: 管理機能（管理者ログイン、メニュー管理）
- **Phase 3**: お客様向け機能（QRコード、メニュー表示、注文）
- **Phase 4**: POS連携（変更記録、ポーリングAPI）
- **Phase 5**: 最適化・本番準備

## Docker環境詳細

**構成サービス:**
- **Laravel アプリケーション** - PHP 8.1、ポート 8080でアクセス
- **MySQL 8.0** - データベース（mobile_order）、ポート 3306
- **Redis** - キャッシュ・セッション・キュー、ポート 6379
- **Mailpit** - メール送信テスト、Web UI: http://localhost:8025

**アクセスURL:**
- アプリケーション: http://localhost:8080
- Mailpit管理画面: http://localhost:8025
- Vite開発サーバー: http://localhost:5173

### アーキテクチャパターン
- **MVC + サービス層**: ビジネスロジックはServiceクラスに集約
- **Repository Pattern**: 複雑なクエリは抽象化
- **Policy Pattern**: 権限管理はPolicyクラスで実装

### 認証システム（3種類）
1. **Laravel Breeze**: 管理者・スタッフ用Web認証
2. **Laravel Sanctum（モバイル）**: お客様用API認証（30日間有効）
3. **Laravel Sanctum（POS）**: POSシステム用API認証（永続・IP制限）

### データベース設計方針
- **命名規則**: snake_case、テーブル名は複数形
- **多言語対応**: JSONカラムで翻訳データ管理
- **監査証跡**: change_logsテーブルでPOS連携用変更追跡
- **ソフトデリート**: 履歴保持が必要なデータは論理削除

### セキュリティ要件
- **OWASP Top 10対策**: 入力検証、XSS、CSRF、SQLインジェクション対策
- **レート制限**: API呼び出し制限（一般60/分、POS120/分）
- **IP制限**: POS APIは店舗固定IP許可リスト
- **権限管理**: Role-Based Access Control (RBAC)

## Laravel の主要技術要素

- **Eloquent ORM** - データベース操作、リレーション管理
- **Livewire 3.6+** - リアルタイムUI更新、Alpine.js統合
- **Mary UI 2.4+** - TailwindCSS ベースUIコンポーネント
- **Laravel Sanctum** - API認証（モバイル・POS）
- **Laravel Breeze** - Web認証（管理者）
- **Vite** - フロントエンドアセットビルド
- **Laravel Sail** - Docker開発環境