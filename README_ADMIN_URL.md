# 管理者画面URL カスタマイズガイド

## 概要
セキュリティ向上のため、管理者画面のURLプレフィックスをカスタマイズできます。デフォルトの `/admin` から任意の文字列に変更可能です。

## 設定方法

### 1. 環境変数での設定
`.env` ファイルで `ADMIN_URL_PREFIX` を変更してください：

```bash
# デフォルト設定
ADMIN_URL_PREFIX=admin

# カスタム設定例
ADMIN_URL_PREFIX=secure_panel_xyz
ADMIN_URL_PREFIX=management_dashboard
ADMIN_URL_PREFIX=admin_$(date +%Y%m)  # 年月を含む例
```

### 2. 設定反映
設定変更後は必ずキャッシュクリアを実行してください：

```bash
./vendor/bin/sail artisan config:clear
```

## URL例

| 設定値 | 管理画面URL |
|--------|------------|
| `admin` | `/admin` (デフォルト) |
| `secure_panel_xyz` | `/secure_panel_xyz` |
| `management` | `/management` |
| `admin_202508` | `/admin_202508` |

## セキュリティ推奨事項

### 1. 本番環境での必須変更
- **絶対にデフォルト値 `admin` を使用しない**
- 推測困難な文字列を使用
- 定期的な変更（3-6ヶ月ごと）

### 2. 命名ガイドライン
```bash
# 良い例
ADMIN_URL_PREFIX=secure_management_2024
ADMIN_URL_PREFIX=control_panel_xyz789
ADMIN_URL_PREFIX=admin_dashboard_$(openssl rand -hex 4)

# 悪い例（推測されやすい）
ADMIN_URL_PREFIX=administrator
ADMIN_URL_PREFIX=manage
ADMIN_URL_PREFIX=backend
```

### 3. アクセス制御
- VPN経由でのアクセス推奨
- アクセスログの定期監視

## ヘルパー関数

`App\Helpers\AdminHelper` クラスで便利な関数を提供：

```php
use App\Helpers\AdminHelper;

// 管理者URLの生成
$adminUrl = AdminHelper::adminUrl('products');  // /secure_panel_xyz/products

// 管理者エリアの判定
if (AdminHelper::isAdminArea()) {
    // 管理者エリアの処理
}

// ルート名の生成
$route = AdminHelper::adminRoute('dashboard');  // admin.dashboard
```

## トラブルシューティング

### 1. 404エラーが発生する場合
```bash
# キャッシュクリア
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear

# ルート確認
./vendor/bin/sail artisan route:list | grep admin
```

### 2. 設定が反映されない場合
```bash
# 設定値確認
./vendor/bin/sail artisan tinker --execute="dump(config('app.admin_prefix'));"

# .envファイルの文法確認
cat .env | grep ADMIN_URL_PREFIX
```

## 注意事項

1. **URL変更後のブックマーク更新**: 管理者に新URLの周知が必要
2. **API連携**: POS側システムでURL変更が必要な場合は調整
3. **監視設定**: アクセスログ監視設定の更新
4. **ドキュメント更新**: 運用手順書の更新

## 本番運用での推奨設定

```bash
# 本番環境 .env 設定例
APP_ENV=production
APP_DEBUG=false
ADMIN_URL_PREFIX=secure_$(openssl rand -hex 8)

# 定期変更スクリプト例（月次実行）
ADMIN_URL_PREFIX=admin_$(date +%Y%m)_$(openssl rand -hex 4)
```

セキュリティ向上のため、本番環境では必ずデフォルト値から変更してください。