# HTTPS環境デプロイメント設定

## 必須環境変数

AWS ALB + EC2環境でHTTPSを使用する場合、以下の環境変数設定が必須です。

### .env設定

```bash
# HTTPS環境の場合
APP_URL=https://your-domain.com
ASSET_URL=https://your-domain.com

# HTTP環境の場合
APP_URL=http://your-domain.com
# ASSET_URLは不要
```

## TrustProxiesミドルウェア

ALB（Application Load Balancer）を使用する場合、`app/Http/Middleware/TrustProxies.php`で以下の設定が必要：

```php
protected $proxies = '*';  // ALBからのリクエストを信頼
```

## デプロイ時のチェックリスト

### 1. 環境変数の設定
- [ ] `APP_URL`をHTTPSのURLに設定
- [ ] `ASSET_URL`をHTTPSのURLに設定（HTTPSの場合のみ）

### 2. Laravel設定のクリア
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 3. 権限設定
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Apacheモジュール（EC2の場合）
```bash
sudo a2enmod headers
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## トラブルシューティング

### CSSが読み込まれない場合
1. `APP_URL`と`ASSET_URL`が正しく設定されているか確認
2. Laravelのキャッシュをクリア
3. ブラウザのキャッシュをクリア

### HTTPSリダイレクトループが発生する場合
1. `TrustProxies.php`の設定を確認
2. ALBのリスナー設定を確認（HTTPとHTTPSの両方が必要）

### Mixed Content警告が出る場合
1. すべてのアセットURLがHTTPSになっているか確認
2. `ASSET_URL`環境変数が設定されているか確認