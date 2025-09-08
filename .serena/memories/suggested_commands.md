# Mobile Order System - 推奨コマンド一覧

## Docker環境（Laravel Sail）
```bash
sail up -d                      # コンテナ起動
sail down                       # コンテナ停止
sail ps                         # 状態確認
sail exec laravel.test bash     # コンテナ内でbash実行
```

## 開発サーバー
```bash
sail npm run dev                # Vite開発サーバー起動（ホットリロード）
sail npm run build              # 本番用ビルド
```

## データベース操作
```bash
sail artisan migrate            # マイグレーション実行
sail artisan migrate:fresh      # 全テーブル削除して再作成
sail artisan migrate:rollback   # 直前のマイグレーションをロールバック
sail artisan db:seed            # シーダー実行
sail artisan tinker             # 対話シェル起動
```

## テスト実行
```bash
sail artisan test               # 全テスト実行
sail artisan test --filter=TestName  # 特定のテスト実行
sail artisan test tests/Feature/     # Feature テストのみ実行
```

## コード品質チェック（タスク完了時に必須）
```bash
sail exec laravel.test vendor/bin/pint        # Laravel Pint実行（コード整形）
sail exec laravel.test vendor/bin/pint --test # ドライラン（変更なし）
```

## モデル・コントローラー作成
```bash
sail artisan make:model Product -mfc   # モデル、マイグレーション、ファクトリー、コントローラー作成
sail artisan make:livewire ProductCard # Livewireコンポーネント作成
sail artisan make:migration create_cart_logs_table  # マイグレーション作成

# 管理画面Livewireコンポーネント作成例
sail artisan make:livewire Admin/Products/ProductIndex
sail artisan make:livewire Admin/Products/ProductEdit
sail artisan make:livewire Admin/Options/OptionIndex
```

## Git操作（Linux環境）
```bash
git status                      # 変更状態確認
git diff                        # 差分確認
git add .                       # 全ファイルをステージング
git commit -m "メッセージ"      # コミット
git log --oneline -10           # 最近のコミット確認
git branch                      # ブランチ一覧
git checkout -b feature/新機能  # 新ブランチ作成・切替
```

## システムユーティリティ（Linux）
```bash
ls -la                          # ファイル一覧（隠しファイル含む）
pwd                            # 現在のディレクトリ
cd ディレクトリ                 # ディレクトリ移動
grep -r "検索文字" .           # 再帰的にテキスト検索
find . -name "*.php"           # ファイル名検索
tail -f storage/logs/laravel.log  # ログファイル監視
```

## 注意事項
- **コード変更後は必ずLaravel Pintを実行**してコード整形を行う
- **テストがある場合は必ず実行**して動作確認を行う
- Dockerコンテナが起動していることを確認してからコマンドを実行する