# Laravel 10 モバイル注文システム 外部ライブラリ仕様書

## 1. システム概要

本ドキュメントは、Laravel 10を使用したモバイル注文システムの開発に必要な外部ライブラリの推奨バージョンと使用方法をまとめたものです。各ライブラリは相互の互換性を検証済みで、モバイル環境での最適なパフォーマンスを実現します。

### 推奨技術スタック
- **Laravel Framework**: 10.48.29
- **Laravel Livewire**: 3.5+
- **Alpine.js**: 3.x（Livewireにバンドル）
- **MaryUI**: v2.x
- **TailwindCSS**: 4.0
- **Laravel Sanctum**: 3.x
- **MySQL**: 8.0+
- **Vite**: 4.x

## 2. フロントエンドフレームワーク

### 2.1 Laravel Livewire 3.0

**バージョン**: 3.5以上  
**PHP要件**: 8.1以上

#### インストール方法
```bash
composer require livewire/livewire
```

#### 基本設定
```php
// config/app.php
'providers' => [
    // ...
    Livewire\LivewireServiceProvider::class,
],
```

#### 使用方法
- Alpine.jsは自動的にバンドルされるため、個別インストール不要
- `$wire`オブジェクトでコンポーネントと直接通信
- `@entangle`ディレクティブで自動状態同期

#### 注意点
- Livewire 2.xからの移行時は`wire:model.defer`が`wire:model`に変更
- ファイルアップロードはS3等のクラウドストレージ推奨

### 2.2 MaryUI

**バージョン**: v2.x  
**依存関係**: daisyUI 4.12.10+

#### インストール方法
```bash
composer require robsontenorio/mary
php artisan mary:install
```

#### 設定
```javascript
// tailwind.config.js
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./vendor/robsontenorio/mary/src/View/Components/**/*.php"
    ],
    // ...
}
```

#### 主要コンポーネント
- フォームコンポーネント（入力、選択、チェックボックス）
- データテーブル（ソート、フィルタリング対応）
- ナビゲーション要素（モバイル最適化済み）

### 2.3 TailwindCSS

**バージョン**: 4.0  
**ビルドツール**: Vite

#### 最適化設定
```javascript
// vite.config.js
export default {
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['alpinejs', '@alpinejs/focus'],
                }
            }
        }
    }
}
```

## 3. 認証・セキュリティ

### 3.1 Laravel Breeze（管理者・スーパーユーザー認証）

**バージョン**: 2.x（Laravel 10対応）  
**用途**: 管理者およびスーパーユーザーのWeb認証

#### インストール方法
```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
php artisan migrate
```

#### カスタマイズ設定
```php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php
protected function authenticated(Request $request, $user)
{
    // 管理者・スーパーユーザーの判定
    if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
        return redirect()->intended(route('admin.dashboard'));
    }
    
    return redirect()->intended(RouteServiceProvider::HOME);
}
```

#### ミドルウェア設定
```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    // ...
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
];
```

#### 管理者用ルート設定
```php
// routes/web.php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('menu-items', AdminProductController::class);
    Route::resource('translations', AdminTranslationController::class);
});

Route::middleware(['auth', 'super_admin'])->prefix('super-admin')->group(function () {
    Route::get('/system-settings', [SystemSettingsController::class, 'index'])->name('super.settings');
    Route::post('/impersonate/{user}', [ImpersonateController::class, 'start'])->name('super.impersonate');
});
```

### 3.2 Laravel Sanctum（API認証）

**バージョン**: 3.x（Laravel 10にデフォルト含有）  
**用途**: 
- お客様向けモバイルアプリのAPI認証
- POSシステム連携のAPI認証

#### モバイルアプリ用設定
```php
// config/sanctum.php
'expiration' => 43200, // 30日間
'token_prefix' => 'mobile_order_',
```

#### POS連携用設定
```php
// config/sanctum.php
'pos_tokens' => [
    'expiration' => null, // 無期限
    'token_prefix' => 'pos_api_',
],
```

#### トークン生成（モバイル用）
```php
$token = $user->createToken(
    $request->device_name,
    ['place-orders', 'view-orders']
)->plainTextToken;
```

#### トークン生成（POS連携用）
```php
// POSシステム用の専用ユーザーを作成
$posUser = User::create([
    'name' => 'POS System - Store #001',
    'email' => 'pos_store001@system.local',
    'password' => Hash::make(Str::random(32)),
    'is_pos_system' => true,
]);

// POS用トークン生成（権限を細かく設定）
$posToken = $posUser->createToken(
    'pos_store_001',
    [
        'pos:sync-menu',        // メニュー同期
        'pos:send-status',      // ステータス更新
        'pos:fetch-orders',     // 注文取得
        'pos:generate-qr',      // QRコード生成
    ]
)->plainTextToken;
```

#### CORS設定
```php
// config/cors.php
'allowed_origins' => [
    'capacitor://localhost',
    'ionic://localhost',
    'http://localhost:3000',
    // POS用IPアドレス（セキュリティ強化）
    'http://192.168.1.100',
    'https://pos.restaurant.local',
],
'supports_credentials' => true,
```

### 3.3 POS連携API専用設定

#### ルート定義
```php
// routes/api.php
Route::prefix('pos/v1')->middleware(['auth:sanctum', 'pos.api'])->group(function () {
    // メニュー同期
    Route::post('/menu/sync', [PosMenuController::class, 'sync'])
        ->middleware('ability:pos:sync-menu');
    
    // 注文取得（ポーリング）
    Route::get('/orders/pending', [PosOrderController::class, 'fetchPending'])
        ->middleware('ability:pos:fetch-orders');
    
    // ステータス更新
    Route::put('/orders/{order}/status', [PosOrderController::class, 'updateStatus'])
        ->middleware('ability:pos:send-status');
    
    // QRコード生成
    Route::post('/qr/generate', [PosQrCodeController::class, 'generate'])
        ->middleware('ability:pos:generate-qr');
    
    // 売り切れ状態更新
    Route::post('/menu/availability', [PosMenuController::class, 'updateAvailability'])
        ->middleware('ability:pos:sync-menu');
});
```

#### POS専用ミドルウェア
```php
// app/Http/Middleware/EnsurePosApiAccess.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePosApiAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user || !$user->is_pos_system) {
            return response()->json([
                'message' => 'Unauthorized. POS system access required.',
            ], 403);
        }
        
        // IPアドレス制限（オプション）
        $allowedIps = config('pos.allowed_ips', []);
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            activity()
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('不正なIPアドレスからのPOS APIアクセス試行');
                
            return response()->json([
                'message' => 'Access denied from this IP address.',
            ], 403);
        }
        
        return $next($request);
    }
}
```

#### レート制限設定
```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting()
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
    
    // POS API専用レート制限
    RateLimiter::for('pos-api', function (Request $request) {
        return Limit::perMinute(300) // POSは高頻度アクセス許可
            ->by($request->user()?->id ?: $request->ip());
    });
    
    // メニュー同期は低頻度
    RateLimiter::for('pos-sync', function (Request $request) {
        return Limit::perHour(10)
            ->by($request->user()?->id ?: $request->ip());
    });
}
```

### 3.4 認証の使い分け

| 認証方式 | 使用ライブラリ | 対象システム | 認証方法 | トークン有効期限 |
|---------|-------------|------------|----------|----------------|
| Web認証 | Laravel Breeze | 管理者・スーパーユーザー | Session/Cookie | セッション依存 |
| API認証（モバイル） | Laravel Sanctum | お客様モバイルアプリ | Bearer Token | 30日間 |
| API認証（POS） | Laravel Sanctum | POSシステム | Bearer Token | 無期限 |

### 3.4 ロールベースアクセス制御（RBAC）

#### ロール定義
```php
// database/seeders/RoleSeeder.php
public function run()
{
    $roles = [
        ['name' => 'super_admin', 'display_name' => 'スーパー管理者'],
        ['name' => 'admin', 'display_name' => '店舗管理者'],
        ['name' => 'staff', 'display_name' => '店舗スタッフ'],
        ['name' => 'staff', 'display_name' => 'お客様'],
    ];
    
    foreach ($roles as $role) {
        Role::create($role);
    }
}
```

#### 権限チェック例
```php
// スーパーユーザー機能
if (auth()->user()->hasRole('super_admin')) {
    // システム全体設定の変更
    // 店舗代理操作
    // 緊急メンテナンスモード切替
}

// 管理者機能
if (auth()->user()->hasRole('admin')) {
    // 多言語翻訳管理
    // 売り切れ状態管理
    // 注文履歴確認
}
```

### 3.5 セキュリティ実装
- Breeze：2要素認証サポート（TOTP）
- Sanctum：デバイス別トークン管理
- 共通：レート制限（1分間に60リクエスト）
- 共通：IPアドレスホワイトリスト（管理者用）

## 4. データベース設定

### 4.1 MySQL設定

**バージョン**: 8.0以上  
**文字セット**: utf8mb4（多言語対応必須）

#### Laravel設定
```php
// config/database.php
'mysql' => [
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'engine' => 'InnoDB',
    'strict' => true,
    'options' => [
        PDO::ATTR_PERSISTENT => true, // 接続プーリング
    ],
],
```

#### パフォーマンス最適化
```ini
# my.cnf
innodb_buffer_pool_size = 2G  # メモリの70-80%
max_connections = 500
query_cache_size = 64M
```

### 4.2 読み書き分離
```php
'mysql' => [
    'read' => [
        'host' => ['192.168.1.1', '192.168.1.2'],
    ],
    'write' => [
        'host' => ['192.168.1.3'],
    ],
    'sticky' => true,
],
```

## 5. リアルタイム更新機能

### 5.1 ポーリング実装（Livewire）

#### 基本的な使い方
```blade
<div wire:poll.10s="refreshOrderStatus">
    <!-- 注文ステータス表示 -->
</div>
```

#### モバイル最適化設定
- `wire:poll.visible`：画面表示時のみポーリング
- 推奨間隔：10〜30秒（バッテリー節約）
- ネットワークエラー時の自動再試行

### 5.2 WebSocket代替案（将来拡張用）

**推奨**: Soketi（Pusher互換プロトコル）

```bash
docker run -p 6001:6001 quay.io/soketi/soketi:latest-16-alpine
```

### 5.3 サーキットブレーカー

**ライブラリ**: ackintosh/ganesha

```bash
composer require ackintosh/ganesha
```

#### 設定例
```php
$ganesha = Builder::withRateStrategy()
    ->timeWindow(30)
    ->failureRateThreshold(50)
    ->minimumRequests(10)
    ->intervalToHalfOpen(5)
    ->build();
```

## 6. QRコード・画像処理

### 6.1 QRコード生成

**ライブラリ**: simplesoftwareio/simple-qrcode v4

```bash
composer require simplesoftwareio/simple-qrcode
```

#### 使用例
```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

// モバイル最適化設定
$qrcode = QrCode::size(250)
    ->errorCorrection('H') // 高エラー訂正
    ->margin(2)
    ->format('svg') // スケーラブル
    ->generate($url);
```

### 6.2 画像処理

**ライブラリ**: intervention/image-laravel 1.x（Laravel 10対応）

```bash
composer require intervention/image-laravel
```

#### モバイル用画像最適化
```php
// 複数サイズ生成
$sizes = [
    'thumb' => [150, 150],
    'mobile' => [400, 400],
    'tablet' => [600, 600],
];

foreach ($sizes as $name => $dimensions) {
    Image::make($file)
        ->fit($dimensions[0], $dimensions[1])
        ->encode('webp', 80)
        ->save("products/{$name}_{$filename}");
}
```

### 6.3 メディア管理

**ライブラリ**: spatie/laravel-medialibrary 10.x

```bash
composer require spatie/laravel-medialibrary
```

#### 設定
```php
// モデルでの使用
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('mobile')
            ->width(400)
            ->height(400)
            ->format('webp')
            ->quality(75);
    }
}
```

## 7. 多言語対応

### 7.1 基本設定

**対応言語**: 日本語、英語、繁体字、簡体字、韓国語

#### ディレクトリ構造
```
/lang
  /ja
    messages.php
    menu.php
  /en
  /zh-TW
  /zh-CN
  /ko
```

### 7.2 データベース翻訳

**ライブラリ**: spatie/laravel-translatable 6.x

```bash
composer require spatie/laravel-translatable
```

#### モデル実装
```php
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;
    
    public $translatable = ['name', 'description'];
}
```

### 7.3 動的翻訳管理

**ライブラリ**: spatie/laravel-translation-loader

```bash
composer require spatie/laravel-translation-loader
```

#### CJK言語の注意点
- UTF8MB4文字セット必須
- フォント：Google Noto CJKフォント推奨
- 行高調整：1.8倍推奨

## 8. ログ・監視

### 8.1 ログ設定

#### チャンネル設定
```php
// config/logging.php
'channels' => [
    'mobile_api' => [
        'driver' => 'daily',
        'path' => storage_path('logs/mobile-api.log'),
        'level' => 'info',
        'days' => 14,
    ],
    'orders' => [
        'driver' => 'daily',
        'path' => storage_path('logs/orders.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

### 8.2 アクティビティログ

**ライブラリ**: spatie/laravel-activitylog 4.x

```bash
composer require spatie/laravel-activitylog
```

#### 使用例
```php
activity()
    ->performedOn($order)
    ->causedBy($user)
    ->withProperties([
        'device_type' => $request->header('X-Device-Type'),
        'app_version' => $request->header('X-App-Version'),
    ])
    ->log('注文が作成されました');
```

### 8.3 エラー監視（本番環境）

**ライブラリ**: sentry/sentry-laravel

```bash
composer require sentry/sentry-laravel
```

#### 設定
```php
// config/sentry.php
'traces_sample_rate' => 0.1, // 10%サンプリング
'profiles_sample_rate' => 0.1,
'send_default_pii' => false, // 個人情報除外
```

## 9. セッション管理

### 9.1 Redis設定

```php
// config/session.php
'driver' => 'redis',
'connection' => 'session',
'lifetime' => 60, // モバイル用：60分
'expire_on_close' => false,
```

### 9.2 マルチデバイス管理

**ライブラリ**: craftsys/laravel-redis-session-enhanced（オプション）

```bash
composer require craftsys/laravel-redis-session-enhanced
```

## 10. エラーハンドリング・リトライ

### 10.1 カスタム例外ハンドラー

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if ($request->is('api/*')) {
        return response()->json([
            'error_code' => $this->getErrorCode($exception),
            'message' => $this->getLocalizedMessage($exception),
            'retry_after' => $this->getRetryAfter($exception),
        ], $this->getStatusCode($exception));
    }
}
```

### 10.2 HTTPクライアントリトライ

```php
Http::retry(3, 100, function ($exception) {
    return $exception instanceof ConnectionException;
})->post($url, $data);
```

### 10.3 失敗ジョブ監視

**ライブラリ**: spatie/laravel-failed-job-monitor

```bash
composer require spatie/laravel-failed-job-monitor
```

## 11. アセット最適化

### 11.1 Vite設定

```javascript
// vite.config.js
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }
                }
            }
        }
    }
});
```

## 12. パフォーマンス最適化

### 12.1 キャッシュ戦略

```php
// 設定キャッシュ
php artisan config:cache
php artisan route:cache
php artisan view:cache

// Redisキャッシュ
Cache::remember('products', 3600, function () {
    return Product::with('translations')->get();
});
```

### 12.2 クエリ最適化

```php
// Eager Loading
$orders = Order::with(['items', 'items.product'])
    ->where('session_id', $sessionId)
    ->get();

// インデックス最適化
Schema::table('orders', function (Blueprint $table) {
    $table->index(['session_id', 'created_at']);
});
```

## 13. バージョン互換性マトリクス

| ライブラリ | 推奨バージョン | Laravel 10互換性 | 備考 |
|---------|------------|---------------|-----|
| Laravel Breeze | 2.x | ✓ | 管理者認証用 |
| Livewire | 3.5+ | ✓ | Alpine.js含む |
| MaryUI | 2.x | ✓ | daisyUI 4.12.10+ |
| Sanctum | 3.x | ✓ | デフォルト含有 |
| Intervention Image | 3.x | ✓ | Laravel専用パッケージ使用 |
| Spatie Media Library | 10.x | ✓ | |
| Simple QrCode | 4.x | ✓ | |
| Spatie Translatable | 6.x | ✓ | |
| Spatie Activity Log | 4.x | ✓ | |

## 14. トラブルシューティング

### 14.1 一般的な問題と解決策

**Livewireコンポーネントが更新されない**
- `wire:key`属性を追加
- `$refresh`メソッドを使用
- Alpine.jsとの競合確認（`x-data`と`wire:`の混在に注意）

**MaryUIコンポーネントが表示されない**
- `npm run build`でアセット再ビルド
- `tailwind.config.js`にMaryUIのパスが含まれているか確認
- `php artisan view:clear`でビューキャッシュクリア

**画像アップロードのメモリエラー**
- `memory_limit`を増加（`php.ini`で256M以上推奨）
- キュー処理に移行
- Intervention Imageの`limitMemory()`メソッド使用

**多言語文字化け**
- データベース照合順序確認（`utf8mb4_unicode_ci`必須）
- `mb_string`拡張確認
- HTTPヘッダーの`Content-Type: application/json; charset=utf-8`設定

**POS連携エラー**
- Sanctumトークンの有効性確認
- IPアドレスホワイトリスト確認
- `change_logs`テーブルのインデックス最適化

### 14.2 パフォーマンス問題

**ポーリング負荷**
- 間隔を延長（30秒推奨）
- `wire:poll.visible`使用
- 変更記録テーブルのインデックス最適化

**画像読み込み遅延**
- WebP形式使用
- CDN導入検討
- 遅延読み込み（`loading="lazy"`）実装

**データベースクエリ遅延**
- Eager Loading使用（`with()`メソッド）
- クエリログ分析（`DB::enableQueryLog()`）
- 適切なインデックス追加

## 15. セキュリティベストプラクティス

### 15.1 API認証

**Breeze（管理者向け）**
- 2要素認証の有効化
- セッションタイムアウト設定（30分推奨）
- IPアドレス制限（管理画面アクセス）
- ログイン試行回数制限

**Sanctum（API向け）**
- Bearerトークン使用
- デバイス別トークン管理
- 定期的なトークンローテーション
- 権限の最小化原則

**POS連携**
- 専用ユーザーアカウント
- IPアドレスホワイトリスト必須
- 細かい権限設定（ability）
- アクセスログの監視

### 15.2 データ検証

**入力検証**
```php
// フォームリクエスト使用
public function rules()
{
    return [
        'order_items' => 'required|array|max:20',
        'order_items.*.quantity' => 'required|integer|min:1|max:10',
        'order_items.*.options' => 'nullable|array',
    ];
}
```

**SQLインジェクション対策**
- Eloquent ORM使用
- パラメータバインディング
- 生SQLの最小化

**XSS防止**
- Bladeテンプレートの自動エスケープ
- `{!! !!}`の使用を制限
- CSPヘッダー設定

### 15.3 ファイルアップロード

**画像検証**
```php
$request->validate([
    'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
]);
```

**ストレージ設定**
- publicディレクトリ外に保存
- ファイル名のランダム化
- ウイルススキャン検討

### 15.4 セッション管理

**Redisセッション**
- 暗号化有効
- HTTPOnly Cookie
- Secure Cookie（HTTPS環境）
- SameSite設定

## 17. 認証システムの統合実装

### 17.1 Breeze + Sanctum統合設定

```php
// app/Providers/AuthServiceProvider.php
public function boot()
{
    // Breezeのログイン後、管理者用トークンも発行可能
    Gate::define('create-api-token', function ($user) {
        return $user->hasRole(['admin', 'super_admin']);
    });
}
```

### 17.2 統合ミドルウェア

```php
// app/Http/Middleware/EnsureUserRole.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Web認証（Breeze）の場合
        if ($request->user() && $request->user()->hasAnyRole($roles)) {
            return $next($request);
        }
        
        // API認証（Sanctum）の場合
        if ($request->bearerToken() && auth('sanctum')->user()) {
            if (auth('sanctum')->user()->hasAnyRole($roles)) {
                return $next($request);
            }
        }
        
        abort(403, 'Unauthorized action.');
    }
}
```

### 17.3 管理画面でのAPI管理

```php
// app/Http/Controllers/Admin/ApiTokenController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = auth()->user()->tokens;
        return view('admin.api-tokens.index', compact('tokens'));
    }
    
    public function store(Request $request)
    {
        $token = auth()->user()->createToken(
            $request->token_name,
            ['admin-api-access']
        );
        
        return back()->with('token', $token->plainTextToken);
    }
    
    public function destroy($tokenId)
    {
        auth()->user()->tokens()->where('id', $tokenId)->delete();
        return back();
    }
}
```

### 17.4 スーパーユーザー成り代わり機能

```php
// app/Http/Controllers/SuperAdmin/ImpersonateController.php
namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use Illuminate\Http\Request;

class ImpersonateController extends Controller
{
    public function start(User $user)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403);
        }
        
        // 元のユーザーIDをセッションに保存
        session(['impersonating_from' => auth()->id()]);
        
        // 対象ユーザーとしてログイン
        auth()->login($user);
        
        activity()
            ->causedBy(session('impersonating_from'))
            ->performedOn($user)
            ->log('スーパーユーザーが成り代わりを開始');
        
        return redirect()->route('home')
            ->with('warning', "現在 {$user->name} として操作中です");
    }
    
    public function stop()
    {
        $originalId = session('impersonating_from');
        if ($originalId) {
            $originalUser = User::find($originalId);
            auth()->login($originalUser);
            session()->forget('impersonating_from');
            
            return redirect()->route('super.dashboard')
                ->with('success', '成り代わりを終了しました');
        }
        
        return redirect()->route('home');
    }
}
```

### 17.5 POS連携APIの実装例（変更記録テーブル方式）

#### 変更記録テーブルによる汎用的なポーリング実装

##### データベース設計
```php
// database/migrations/create_change_logs_table.php
Schema::create('change_logs', function (Blueprint $table) {
    $table->id();
    $table->string('entity_type'); // 'order', 'menu_item', 'inventory' など
    $table->unsignedBigInteger('entity_id');
    $table->string('action'); // 'created', 'updated', 'deleted'
    $table->json('changes')->nullable(); // 変更内容の詳細
    $table->boolean('is_synced')->default(false); // POS同期済みフラグ
    $table->timestamp('synced_at')->nullable();
    $table->string('synced_by')->nullable(); // 同期したPOSのID
    $table->timestamps();
    
    // インデックス
    $table->index(['entity_type', 'is_synced', 'created_at']);
    $table->index(['synced_by', 'synced_at']);
});
```

##### 変更記録モデル
```php
// app/Models/ChangeLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeLog extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'changes',
        'is_synced',
        'synced_at',
        'synced_by',
    ];
    
    protected $casts = [
        'changes' => 'array',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
    ];
    
    public function entity()
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }
}
```

##### 変更記録トレイト
```php
// app/Traits/LogsChanges.php
namespace App\Traits;

use App\Models\ChangeLog;

trait LogsChanges
{
    public static function bootLogsChanges()
    {
        static::created(function ($model) {
            ChangeLog::create([
                'entity_type' => get_class($model),
                'entity_id' => $model->id,
                'action' => 'created',
                'changes' => $model->toArray(),
            ]);
        });
        
        static::updated(function ($model) {
            $changes = $model->getDirty();
            if (!empty($changes)) {
                ChangeLog::create([
                    'entity_type' => get_class($model),
                    'entity_id' => $model->id,
                    'action' => 'updated',
                    'changes' => $changes,
                ]);
            }
        });
        
        static::deleted(function ($model) {
            ChangeLog::create([
                'entity_type' => get_class($model),
                'entity_id' => $model->id,
                'action' => 'deleted',
                'changes' => ['deleted_at' => now()],
            ]);
        });
    }
}
```

##### 注文モデルへの適用
```php
// app/Models/Order.php
namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use LogsChanges;
    
    // 既存のコード...
}
```

#### 改良版ポーリングAPI
```php
// app/Http/Controllers/Api/Pos/PosPollingController.php
namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\ChangeLog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosPollingController extends Controller
{
    public function checkChanges(Request $request)
    {
        $validated = $request->validate([
            'last_sync_id' => 'nullable|integer', // 最後に同期したchange_logのID
            'entity_types' => 'nullable|array',   // 監視対象のエンティティタイプ
            'limit' => 'nullable|integer|max:100',
        ]);
        
        $query = ChangeLog::where('is_synced', false);
        
        // 最後の同期ID以降の変更のみ取得
        if (isset($validated['last_sync_id'])) {
            $query->where('id', '>', $validated['last_sync_id']);
        }
        
        // エンティティタイプでフィルタ
        if (isset($validated['entity_types'])) {
            $query->whereIn('entity_type', $validated['entity_types']);
        }
        
        $changes = $query->orderBy('id', 'asc')
            ->limit($validated['limit'] ?? 50)
            ->get();
        
        return response()->json([
            'has_changes' => $changes->isNotEmpty(),
            'changes_count' => $changes->count(),
            'last_change_id' => $changes->last()?->id,
            'changes' => $changes->map(function ($change) {
                return [
                    'id' => $change->id,
                    'entity_type' => class_basename($change->entity_type),
                    'entity_id' => $change->entity_id,
                    'action' => $change->action,
                    'created_at' => $change->created_at->toIso8601String(),
                ];
            }),
        ]);
    }
    
    public function fetchChangedEntities(Request $request)
    {
        $validated = $request->validate([
            'change_ids' => 'required|array',
            'change_ids.*' => 'required|integer|exists:change_logs,id',
        ]);
        
        $changes = ChangeLog::whereIn('id', $validated['change_ids'])
            ->where('is_synced', false)
            ->get();
        
        $result = [];
        
        DB::transaction(function () use ($changes, $request, &$result) {
            foreach ($changes->groupBy('entity_type') as $entityType => $entityChanges) {
                switch ($entityType) {
                    case 'App\Models\Order':
                        $result['orders'] = $this->fetchOrders($entityChanges);
                        break;
                    case 'App\Models\Product':
                        $result['products'] = $this->fetchProducts($entityChanges);
                        break;
                    // 将来的な拡張用
                    case 'App\Models\Inventory':
                        $result['inventory'] = $this->fetchInventory($entityChanges);
                        break;
                }
            }
            
            // 同期済みフラグを更新
            ChangeLog::whereIn('id', $validated['change_ids'])->update([
                'is_synced' => true,
                'synced_at' => now(),
                'synced_by' => $request->user()->name,
            ]);
        });
        
        return response()->json([
            'success' => true,
            'synced_count' => count($validated['change_ids']),
            'data' => $result,
        ]);
    }
    
    private function fetchOrders($changes)
    {
        $orderIds = $changes->pluck('entity_id')->unique();
        
        return Order::whereIn('id', $orderIds)
            ->with(['items.product', 'session'])
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'table_number' => $order->session->table_number,
                    'status' => $order->status,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'pos_id' => $item->product->pos_id,
                            'name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'options' => $item->options,
                            'price' => $item->price,
                        ];
                    }),
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at->toIso8601String(),
                    'updated_at' => $order->updated_at->toIso8601String(),
                ];
            });
    }
    
    private function fetchProducts($changes)
    {
        // メニューアイテムの変更を取得
        $menuItemIds = $changes->pluck('entity_id')->unique();
        
        return Product::whereIn('id', $menuItemIds)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'pos_id' => $item->pos_id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'is_available' => $item->is_available,
                    'updated_at' => $item->updated_at->toIso8601String(),
                ];
            });
    }
    
    private function fetchInventory($changes)
    {
        // 将来的な在庫管理機能用
        return [];
    }
}
```

#### 変更記録のクリーンアップ
```php
// app/Console/Commands/CleanupChangeLogs.php
namespace App\Console\Commands;

use App\Models\ChangeLog;
use Illuminate\Console\Command;

class CleanupChangeLogs extends Command
{
    protected $signature = 'changelogs:cleanup {--days=30}';
    protected $description = 'Clean up old synced change logs';
    
    public function handle()
    {
        $days = $this->option('days');
        
        $deleted = ChangeLog::where('is_synced', true)
            ->where('synced_at', '<', now()->subDays($days))
            ->delete();
        
        $this->info("Deleted {$deleted} old change logs.");
    }
}

// Kernel.phpでスケジュール登録
$schedule->command('changelogs:cleanup')->daily();
```

### 17.6 メニュー同期API（従来方式）

```php
// app/Http/Controllers/Api/Pos/PosMenuController.php
namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosMenuController extends Controller
{
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.pos_id' => 'required|string',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|integer|min:0',
            'items.*.category' => 'required|string',
            'items.*.image_url' => 'nullable|url',
            'items.*.is_available' => 'boolean',
        ]);
        
        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $item) {
                Product::updateOrCreate(
                    ['pos_id' => $item['pos_id']],
                    [
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'category' => $item['category'],
                        'image_url' => $item['image_url'] ?? null,
                        'is_available' => $item['is_available'] ?? true,
                    ]
                );
            }
        });
        
        activity()
            ->causedBy($request->user())
            ->log('POSメニュー同期完了: ' . count($validated['items']) . '件');
        
        return response()->json([
            'success' => true,
            'synced_count' => count($validated['items']),
        ]);
    }
}
```

### 17.7 POS側の実装例

```php
// POSシステム側の擬似コード
class PosPollingService
{
    private $lastSyncId = null;
    private $apiToken;
    private $apiUrl;
    
    public function poll()
    {
        // 1. 変更確認
        $checkResponse = $this->httpClient->get($this->apiUrl . '/pos/v1/changes/check', [
            'headers' => ['Authorization' => 'Bearer ' . $this->apiToken],
            'query' => [
                'last_sync_id' => $this->lastSyncId,
                'entity_types' => ['App\Models\Order', 'App\Models\Product'],
            ],
        ]);
        
        if ($checkResponse['has_changes']) {
            // 2. 変更されたエンティティを取得
            $changeIds = array_column($checkResponse['changes'], 'id');
            
            $fetchResponse = $this->httpClient->post($this->apiUrl . '/pos/v1/changes/fetch', [
                'headers' => ['Authorization' => 'Bearer ' . $this->apiToken],
                'json' => ['change_ids' => $changeIds],
            ]);
            
            // 3. 取得したデータを処理
            if (isset($fetchResponse['data']['orders'])) {
                $this->processOrders($fetchResponse['data']['orders']);
            }
            
            if (isset($fetchResponse['data']['products'])) {
                $this->processProducts($fetchResponse['data']['products']);
            }
            
            // 4. 最後の同期IDを更新
            $this->lastSyncId = $checkResponse['last_change_id'];
            $this->saveLastSyncId();
        }
    }
}
```

### 17.8 POSトークン管理コマンド

```php
// app/Console/Commands/GeneratePosToken.php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GeneratePosToken extends Command
{
    protected $signature = 'pos:generate-token {store_id} {--abilities=*}';
    protected $description = 'Generate API token for POS system';
    
    public function handle()
    {
        $storeId = $this->argument('store_id');
        $abilities = $this->option('abilities');
        
        // 既存のPOSユーザーを検索または作成
        $posUser = User::firstOrCreate(
            ['email' => "pos_store{$storeId}@system.local"],
            [
                'name' => "POS System - Store #{$storeId}",
                'password' => Hash::make(Str::random(32)),
                'is_pos_system' => true,
            ]
        );
        
        // 既存トークンを無効化
        $posUser->tokens()->delete();
        
        // 新しいトークンを生成
        $defaultAbilities = ['pos:sync-menu', 'pos:send-status', 'pos:fetch-orders', 'pos:generate-qr'];
        $token = $posUser->createToken(
            "pos_store_{$storeId}",
            $abilities === ['*'] ? $defaultAbilities : $abilities
        );
        
        $this->info("POS Token generated successfully!");
        $this->line("Store ID: {$storeId}");
        $this->line("Token: {$token->plainTextToken}");
        $this->warn("Please save this token securely. It won't be shown again.");
        
        // 環境変数として出力（オプション）
        $this->line("\nEnvironment variable:");
        $this->line("POS_API_TOKEN={$token->plainTextToken}");
    }
}
```

## 18. 開発環境構築手順

### 18.1 初期セットアップ

```bash
# プロジェクト作成
composer create-project laravel/laravel mobile-order-system "10.*"
cd mobile-order-system

# 基本パッケージインストール
composer require laravel/breeze --dev
composer require livewire/livewire
composer require robsontenorio/mary

# 追加パッケージ
composer require simplesoftwareio/simple-qrcode
composer require intervention/image-laravel
composer require spatie/laravel-medialibrary
composer require spatie/laravel-translatable
composer require spatie/laravel-activitylog
composer require ackintosh/ganesha

# 開発用パッケージ
composer require --dev laravel/sail

# Breeze初期化
php artisan breeze:install blade
npm install && npm run build

# MaryUI初期化
php artisan mary:install
```

### 18.2 データベース設定

```bash
# .env設定
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mobile_order
DB_USERNAME=root
DB_PASSWORD=password

# マイグレーション作成
php artisan make:migration create_change_logs_table
php artisan make:migration add_pos_fields_to_users_table
php artisan make:migration create_sessions_table
php artisan make:migration create_orders_table
php artisan make:migration create_products_table

# 実行
php artisan migrate
```

### 18.3 初期データ投入

```bash
# シーダー作成
php artisan make:seeder RoleSeeder
php artisan make:seeder AdminUserSeeder
php artisan make:seeder SampleMenuSeeder

# 実行
php artisan db:seed
```

## 19. まとめ

本ドキュメントで示した技術スタックは、Laravel 10でのモバイル注文システム開発に最適化されています。Laravel Breezeによる管理者認証とLaravel Sanctumによるモバイル認証を組み合わせることで、セキュアで柔軟な認証システムを実現できます。各ライブラリは相互の互換性を確認済みで、実装時の衝突を最小限に抑えられます。定期的なセキュリティアップデートの適用と、パフォーマンス監視の実施を推奨します。