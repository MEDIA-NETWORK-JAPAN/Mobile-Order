# 最新版ソースコード再デプロイ手順

## 📋 前提条件
- EC2インスタンス: `i-0c31ed61ef10b9017` (3.112.36.235)
- ALB: `mobile-order-alb`
- ドメイン: `mnj2000.com`
- SSH鍵: `mobile-order-key.pem`

## 1️⃣ ローカル環境の準備

### 1.1 最新コードの取得
```bash
# GitHubから最新版を取得
git pull origin main

# または特定ブランチ
git checkout feature/your-branch
git pull origin feature/your-branch
```

### 1.2 必須ファイルの確認
```bash
# HTTPS対応の確認
grep "protected \$proxies = '\*'" app/Http/Middleware/TrustProxies.php
grep "ASSET_URL" .env.example
```

### 1.3 ビルドアセットの生成
```bash
# 依存関係のインストール
npm install

# 本番用ビルド
npm run build

# ビルドファイルの確認
ls -la public/build/
```

## 2️⃣ EC2へのデプロイ

### 2.1 SSH接続
```bash
ssh -i mobile-order-key.pem ubuntu@3.112.36.235
```

### 2.2 バックアップ作成
```bash
# EC2上で実行
cd /var/www/html
sudo tar -czf mobile-order-backup-$(date +%Y%m%d-%H%M%S).tar.gz mobile-order/
```

### 2.3 コードの更新

#### 方法A: Git Pull（推奨）
```bash
cd /var/www/html/mobile-order
sudo git fetch origin
sudo git pull origin main
```

#### 方法B: 手動アップロード
```bash
# ローカルから実行
rsync -avz --exclude='.env' \
  --exclude='storage/*' \
  --exclude='vendor' \
  --exclude='node_modules' \
  -e "ssh -i mobile-order-key.pem" \
  ./ ubuntu@3.112.36.235:/tmp/mobile-order-new/

# EC2上で実行
sudo rsync -av /tmp/mobile-order-new/ /var/www/html/mobile-order/
```

## 3️⃣ 環境設定の更新

### 3.1 環境変数の設定
```bash
# EC2上で実行
cd /var/www/html/mobile-order

# .envファイルの編集
sudo nano .env

# 必須設定
APP_URL=https://mnj2000.com
ASSET_URL=https://mnj2000.com
APP_ENV=production
APP_DEBUG=false
```

### 3.2 Composerパッケージの更新
```bash
# 本番環境用インストール
sudo composer install --optimize-autoloader --no-dev

# または開発環境も含む場合
sudo composer install
```

### 3.3 データベースマイグレーション
```bash
# マイグレーション状態確認
sudo php artisan migrate:status

# マイグレーション実行
sudo php artisan migrate --force

# シーダー実行（必要な場合）
sudo php artisan db:seed --force
```

## 4️⃣ Laravel最適化

### 4.1 キャッシュクリア
```bash
sudo php artisan config:clear
sudo php artisan cache:clear
sudo php artisan view:clear
sudo php artisan route:clear
```

### 4.2 本番最適化
```bash
# 設定キャッシュ
sudo php artisan config:cache

# ルートキャッシュ
sudo php artisan route:cache

# ビューキャッシュ
sudo php artisan view:cache

# オートローダー最適化
sudo composer dump-autoload --optimize
```

## 5️⃣ 権限設定

```bash
# 所有者とグループの設定
sudo chown -R www-data:www-data /var/www/html/mobile-order

# ディレクトリ権限
sudo find /var/www/html/mobile-order -type d -exec chmod 755 {} \;

# ファイル権限
sudo find /var/www/html/mobile-order -type f -exec chmod 644 {} \;

# 書き込み可能ディレクトリ
sudo chmod -R 775 /var/www/html/mobile-order/storage
sudo chmod -R 775 /var/www/html/mobile-order/bootstrap/cache
```

## 6️⃣ Webサーバー再起動

```bash
# Apache再起動
sudo systemctl restart apache2

# PHP-FPM再起動（使用している場合）
sudo systemctl restart php8.2-fpm
```

## 7️⃣ 動作確認

### 7.1 基本確認
```bash
# HTTPSアクセス確認
curl -I https://mnj2000.com

# ログ確認
tail -f /var/www/html/mobile-order/storage/logs/laravel.log
```

### 7.2 ブラウザ確認
- [ ] https://mnj2000.com - トップページ
- [ ] https://mnj2000.com/login - ログイン画面
- [ ] https://mnj2000.com/admin - 管理画面
- [ ] CSS/JSが正常に読み込まれているか
- [ ] HTTPSの鍵マークが表示されているか

## 8️⃣ トラブルシューティング

### エラー: 500 Internal Server Error
```bash
# ログ確認
sudo tail -100 /var/log/apache2/error.log
sudo tail -100 /var/www/html/mobile-order/storage/logs/laravel.log

# 権限再設定
sudo chown -R www-data:www-data storage bootstrap/cache
```

### エラー: CSS/JSが読み込まれない
```bash
# キャッシュクリア
sudo php artisan config:clear
sudo php artisan view:clear

# APP_URL確認
grep APP_URL .env
```

### エラー: Mixed Content警告
```bash
# .env確認
grep ASSET_URL .env
# ASSET_URLがhttpsになっているか確認
```

## 9️⃣ ロールバック手順

問題が発生した場合：
```bash
# バックアップから復元
cd /var/www/html
sudo rm -rf mobile-order
sudo tar -xzf mobile-order-backup-[タイムスタンプ].tar.gz
sudo systemctl restart apache2
```

## 🔒 セキュリティチェックリスト

- [ ] APP_DEBUG=false に設定
- [ ] .envファイルの権限が適切（644または600）
- [ ] 不要なファイルが削除されている
- [ ] mobile-order-key.pemがサーバーに残っていない

## 📝 注意事項

1. **本番環境では必ずAPP_DEBUG=falseに設定**
2. **デプロイ前に必ずバックアップを作成**
3. **データベースマイグレーションは慎重に実行**
4. **ピークタイムを避けてデプロイ実施**
5. **デプロイ後は必ず動作確認を実施**

---
最終更新: 2025-09-09