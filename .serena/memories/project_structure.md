# Mobile Order System - プロジェクト構造

## ルートディレクトリ構成
```
mobile-order/
├── app/                    # アプリケーションロジック
├── bootstrap/              # Laravel起動ファイル
├── config/                 # 設定ファイル
├── database/               # DB関連（マイグレーション、シーダー等）
├── docker/                 # Docker設定ファイル
├── ecs/                    # AWS ECS設定（本番環境）
├── public/                 # 公開ディレクトリ
├── resources/              # ビュー、CSS、JS等のリソース
├── routes/                 # ルーティング定義
├── storage/                # ログ、キャッシュ、セッション等
├── tests/                  # テストコード
├── .claude/                # 設計ドキュメント（22文書）
├── .serena/                # Serena MCP設定
├── artisan                 # Laravelコマンドラインツール
├── composer.json           # PHP依存関係
├── package.json            # Node.js依存関係
├── docker-compose.yml      # 開発環境Docker設定
├── docker-compose.production.yml # 本番環境Docker設定
├── tailwind.config.js      # TailwindCSS設定
├── vite.config.js          # Viteビルド設定
└── CLAUDE.md              # Claude Code用ガイドライン
```

## app/ディレクトリ詳細
```
app/
├── Console/                # Artisanコマンド
├── Exceptions/             # 例外ハンドラー
├── Helpers/                # ヘルパー関数
├── Http/
│   ├── Controllers/        # APIコントローラー、Webコントローラー
│   ├── Middleware/         # 認証、権限チェック等
│   └── Requests/           # フォームリクエスト
├── Livewire/               # Livewireコンポーネント
│   └── Admin/              # 管理画面コンポーネント
│       ├── Products/       # 商品管理
│       ├── Categories/     # カテゴリ管理
│       └── Options/        # オプション管理
├── Models/                 # Eloquentモデル
├── Policies/               # 認可ポリシー
├── Providers/              # サービスプロバイダー
├── Services/               # ビジネスロジック
└── View/                   # Viewコンポーザー等
```

## resources/ディレクトリ詳細
```
resources/
├── css/
│   └── app.css            # TailwindCSSエントリーポイント
├── js/
│   └── app.js             # JavaScript/Alpine.jsエントリーポイント
└── views/
    ├── auth/              # 認証関連画面
    ├── components/        # Bladeコンポーネント
    ├── layouts/           # レイアウトテンプレート
    ├── livewire/          # Livewireコンポーネントビュー
    │   └── admin/         # 管理画面ビュー
    └── welcome.blade.php  # トップページ
```

## database/ディレクトリ詳細
```
database/
├── factories/             # モデルファクトリー（テストデータ生成）
├── migrations/            # DBマイグレーション
└── seeders/              # DBシーダー（初期データ投入）
```

## .claude/設計ドキュメント構造
```
.claude/
├── 00_project/            # プロジェクト基礎資料
├── 01_development_docs/   # 開発技術設計書（16文書）
│   ├── 01_architecture_design.md
│   ├── 02_database_design.md
│   ├── 03_api_design.md
│   └── ...
├── 02_design_system/      # デザインシステム（5文書）
├── 03_library_best_practices/  # ライブラリベストプラクティス
└── 04_implementation/     # 実装関連ドキュメント
```

## 重要なファイル
- **.env**: 環境変数（Git管理外）
- **.env.example**: 環境変数テンプレート
- **composer.json**: PHP依存関係定義
- **package.json**: Node.js依存関係定義
- **phpunit.xml**: PHPUnitテスト設定
- **tailwind.config.js**: TailwindCSS設定
- **vite.config.js**: Viteビルド設定
- **docker-compose.yml**: Docker Compose設定

## 統一商品マスター設計
```
products（商品マスター）
├── メイン商品（ラーメン、寿司など）
├── オプション商品（チャーシュー、ネギなど）
└── サイズ商品（大盛り、特盛りなど）

※すべてproductsテーブルで管理し、関連はcategory_product、product_to_optionsで構築
```