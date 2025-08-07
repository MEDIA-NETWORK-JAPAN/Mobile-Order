# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## プロジェクト概要

**Mobile Order System** - 飲食店向けモバイル注文システム
- QRコード読み取りによる注文開始
- 商品のオプション選択（麺の硬さ、トッピング等）
- リアルタイム在庫管理（売り切れ/未入荷/準備中）
- 多言語対応（日本語、英語、中国語繁体/簡体、韓国語）
- POS連携（Laravel Sanctum認証）

## 開発コマンド

### Docker環境（Laravel Sail）
```bash
sail up -d                      # コンテナ起動
sail down                       # コンテナ停止
sail ps                         # 状態確認
sail exec laravel.test bash     # コンテナ内でbash実行
```

### データベース操作
```bash
sail artisan migrate            # マイグレーション実行
sail artisan migrate:fresh      # 全テーブル削除して再作成
sail artisan migrate:rollback   # 直前のマイグレーションをロールバック
sail artisan db:seed            # シーダー実行
sail artisan tinker             # 対話シェル起動
```

### 開発サーバー
```bash
sail npm run dev                # Vite開発サーバー起動（ホットリロード）
sail npm run build              # 本番用ビルド
```

### テスト実行
```bash
sail artisan test               # 全テスト実行
sail artisan test --filter=TestName  # 特定のテスト実行
sail artisan test tests/Feature/     # Feature テストのみ実行
```

### コード品質
```bash
sail exec laravel.test vendor/bin/pint        # Laravel Pint実行
sail exec laravel.test vendor/bin/pint --test # ドライラン（変更なし）
```

### モデル・コントローラー作成
```bash
sail artisan make:model Product -mfc   # モデル、マイグレーション、ファクトリー、コントローラー作成
sail artisan make:livewire ProductCard # Livewireコンポーネント作成
sail artisan make:migration create_cart_logs_table  # マイグレーション作成
```

## アーキテクチャ概要

### 統一商品マスター設計
```
products（商品マスター）
├── メイン商品（ラーメン、寿司など）
├── オプション商品（チャーシュー、ネギなど）
└── サイズ商品（大盛り、特盛りなど）

※すべてproductsテーブルで管理し、関連はcategory_product、option_detailで構築
```

### 認証システム（2層認証 + 管理・POS）
1. **2層認証システム（お客様用）**:
   - **第1層**: 席セッション（QRコード→同席者間での注文履歴共有）
   - **第2層**: ゲストセッション（個人識別・不正防止・端末特定）
2. **管理者認証**: Laravel Breeze（セッション/Cookie）
3. **POS認証**: Laravel Sanctum（Bearer Token + IP制限）

### セッション管理（2層ハイブリッド方式）
- **第1層 - 席セッション**: QRコード読み取り後の席管理（sessionsテーブル）
  - 同席者間での注文履歴共有
  - 3時間TTL（席の利用時間）
- **第2層 - ゲストセッション**: 個人端末の識別管理（Redis: guest_session:{token}）
  - 個人識別・不正防止・端末特定
  - 30分TTL（アクティビティで自動延長）
- **カートログ**: 全操作履歴をDBに記録（cart_logsテーブル）

### カート管理（ハイブリッド方式）
- **Redisキャッシュ**: 高速アクセス用（30分TTL、自動延長）
  - `guest_session:{token}` - セッション情報
  - `guest_cart:{token}` - カートアイテム
- **DBログ**: 全操作履歴を永続化（cart_logsテーブル）
  - 成功/失敗フラグ（is_success）
  - エラー情報（error_code, error_message）
  - 操作時の価格とカート合計
  - デバイス情報（fingerprint, IP, UA）

### 重要な設計判断
- **マイナス価格対応**: 値引き商品のため価格フィールドはマイナス値を許可
- **ブラウザバック無効化**: 注文フロー中の誤操作防止のため実装
- **availability_status**: ENUM型で4状態管理（available/sold_out/not_arrived/preparing）

## ディレクトリ構造

```
app/
├── Http/
│   ├── Controllers/      # API・Webコントローラー
│   ├── Middleware/       # 認証・権限チェック
│   └── Livewire/         # Livewireコンポーネント
├── Models/               # Eloquentモデル
├── Services/             # ビジネスロジック
└── Policies/             # 権限管理

resources/
├── views/
│   ├── livewire/         # Livewireビュー
│   └── components/       # Bladeコンポーネント
├── css/                  # TailwindCSS
└── js/                   # Alpine.js

database/
├── migrations/           # スキーマ定義
├── factories/            # テストデータ生成
└── seeders/              # 初期データ投入
```

## 作業実施ルール

### 🚨 必須：作業前の許可確認
**作業を実施する前に必ず許可を取ってから開始してください**
- 修正対象ファイル、修正内容、影響範囲を明確に提示
- ユーザーの許可を得てから作業開始
- 完了後に結果を報告

## 開発フロー状況

- **Phase 0**: プロジェクト初期化とドキュメント整備 ✅ 完了
- **Phase 1**: 基盤構築（データベース、認証システム） ← 現在
- **Phase 2**: 管理機能（管理者ログイン、メニュー管理）
- **Phase 3**: お客様向け機能（QRコード、メニュー表示、注文）
- **Phase 4**: POS連携（変更記録、ポーリングAPI）
- **Phase 5**: 最適化・本番準備

## 設計ドキュメント参照

詳細な設計は`.claude/`ディレクトリ内のドキュメントを参照：
- **データベース設計**: `.claude/01_development_docs/02_database_design.md`
- **API設計**: `.claude/01_development_docs/03_api_design.md`
- **認証設計**: `.claude/01_development_docs/13_auth_authorization_design.md`
- **デザインシステム**: `.claude/02_design_system/`配下の各ドキュメント

## Docker環境URL

- **アプリケーション**: http://localhost:8080
- **Mailpit（メール確認）**: http://localhost:8025
- **phpMyAdmin（DB管理）**: http://localhost:8090
- **MySQL**: localhost:3306 (DB: mobile_order)
- **Redis**: localhost:6379