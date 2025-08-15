# 認証・認可設計書

## 📚 目次

- [1. 認証・認可概要](#1-認証認可概要)
  - [1.1 認証方式](#11-認証方式)
  - [1.2 2層認証システムの設計思想](#12-2層認証システムの設計思想)
  - [1.3 セッション管理の統一原則](#13-セッション管理の統一原則)
  - [1.4 ユーザー役割](#14-ユーザー役割)
- [2. Laravel Breeze（Web認証）](#2-laravel-breezeweb認証)
  - [2.1 対象ユーザー](#21-対象ユーザー)
  - [2.2 認証機能](#22-認証機能)
  - [2.3 セキュリティ設定](#23-セキュリティ設定)
  - [2.4 ミドルウェア構成](#24-ミドルウェア構成)
- [3. ゲスト認証（モバイルAPI）](#3-ゲスト認証モバイルapi)
  - [3.1 対象ユーザー](#31-対象ユーザー)
  - [3.2 認証フロー](#32-認証フロー)
  - [3.3 DBセッション設定](#33-dbセッション設定)
  - [3.4 API認証実装](#34-api認証実装)
  - [3.5 ゲスト認証実装](#35-ゲスト認証実装)
- [4. Laravel Sanctum（POS API）](#4-laravel-sanctumpos-api)
  - [4.1 対象システム](#41-対象システム)
  - [4.2 認証方式](#42-認証方式)
  - [4.3 POS認証設定](#43-pos認証設定)
  - [4.4 POS認証ミドルウェア](#44-pos認証ミドルウェア)
- [5. 権限管理システム](#5-権限管理システム)
  - [5.1 権限マトリクス](#51-権限マトリクス)
  - [5.2 権限チェック実装](#52-権限チェック実装)
- [6. セキュリティ対策](#6-セキュリティ対策)
  - [6.1 レート制限](#61-レート制限)
  - [6.2 CSRF保護](#62-csrf保護)
  - [6.3 CORS設定](#63-cors設定)
- [7. セッション管理](#7-セッション管理)
  - [7.1 QRコード セッション（URLパラメータ方式）](#71-qrコード-セッションurlパラメータ方式)
  - [7.2 セッション クリーンアップ](#72-セッション-クリーンアップ)
- [8. API認証フロー詳細](#8-api認証フロー詳細)
  - [8.1 モバイル認証フロー](#81-モバイル認証フロー)
  - [8.2 POS認証フロー](#82-pos認証フロー)
- [9. ログ・監査](#9-ログ監査)
  - [9.1 認証ログ](#91-認証ログ)
  - [9.2 API アクセスログ](#92-api-アクセスログ)
- [10. テスト設計](#10-テスト設計)
  - [10.1 認証テスト](#101-認証テスト)

---

## 1. 認証・認可概要

### 1.1 認証方式
本システムでは3種類の認証方式を使い分け、各ユーザータイプに最適な認証機能を提供します。

1. **Laravel Breeze**（Web認証）- 管理者・スタッフ用
2. **2層認証システム**（モバイルAPI）- お客様用
3. **Laravel Sanctum**（POS API）- POSシステム用

### 1.2 2層認証システムの設計思想

お客様向けモバイルAPIでは、セキュリティと利便性を両立するため2層の認証・識別システムを採用しています：

#### **第1層: 席セッション認証（sessions）**
- **目的**: 同席者間での注文履歴共有
- **生成元**: **POS端末のみ**（SESSION_POS_xxx形式）
- **管理場所**: データベース（sessionsテーブル）
- **識別子**: POS生成 session_id → WebでURL化
- **有効期限**: 固定QRモード時は無期限（NULL）、都度発行モード時は3時間TTL
- **共有範囲**: 同じテーブルの全利用者

#### **第2層: ゲストセッション認証（DB）**
- **目的**: 個人識別・不正アクセス防止・端末特定
- **生成**: スマホアクセス時に自動生成
- **管理場所**: データベース（guest_sessionsテーブル）
- **識別子**: guest_token + device_fingerprint
- **有効期限**: 30分（アクティビティで自動延長）
- **特定機能**: 注文履歴から「誰が注文したか」を視覚化

#### **統一フローによる価値**
```
【POS起点】      【Web受信】       【実現される価値】
席セッションID生成 + URL発行・管理    → 一元管理システム
ハンディ注文可能   + スマホ注文可能   → 柔軟な注文手段
障害時単独動作   + 復旧時自動同期  → 高い可用性
```

### 1.3 セッション管理の統一原則
```
【重要な設計原則】
1. 全ての席セッションIDはPOS端末で生成
2. WebサーバーはセッションIDを受け取りのみ
3. 障害時はcloud_synced=FALSEフラグで管理
4. 復旧時は自動同期でデータ整合性保証
5. QRコード運用モード（固定/都度発行）は店舗単位で設定
```

### 1.4 ユーザー役割
```php
enum UserRole: string 
{
    case SUPER_ADMIN = 'super_admin';    // システム提供者
    case ADMIN = 'admin';                // 店舗管理者  
    case STAFF = 'staff';                // 店舗スタッフ
    case POS_SYSTEM = 'pos_system';      // POSシステム
}
```

## 2. Laravel Breeze（Web認証）

### 2.1 対象ユーザー
- **SUPER_ADMIN**: システム提供者
- **ADMIN**: 店舗管理者
- **STAFF**: 店舗スタッフ

### 2.2 認証機能
- ログイン・ログアウト
- パスワードリセット
- メール認証
- Remember Me機能
- セッション管理（アクティビティベース自動延長）

### 2.3 セキュリティ設定
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],

// config/session.php
'lifetime' => 180, // 3時間（管理者セッション、アクティビティで自動延長）
'expire_on_close' => false,
'encrypt' => true,
'http_only' => true,
'same_site' => 'lax',
```

### 2.4 ミドルウェア構成
```php
// Kernel.php でのミドルウェア登録
protected $middlewareGroups = [
    'web' => [
        // ... 他のミドルウェア
        \App\Http\Middleware\ExtendSessionOnActivity::class,
    ],
];

// 管理者認証
class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        if (!in_array(auth()->user()->role, ['super_admin', 'admin', 'staff'])) {
            abort(403, 'アクセス権限がありません');
        }
        
        return $next($request);
    }
}

// スーパーアドミン限定
class SuperAdminMiddleware 
{
    public function handle($request, Closure $next)
    {
        if (auth()->user()?->role !== 'super_admin') {
            abort(403, 'スーパーアドミン権限が必要です');
        }
        
        return $next($request);
    }
}

// セッションアクティビティ延長
class ExtendSessionOnActivity
{
    public function handle($request, Closure $next)
    {
        if ($request->hasSession() && $request->user()) {
            // 最後のアクティビティから2時間でタイムアウト
            $lastActivity = session('last_activity', now()->timestamp);
            $timeout = config('session.lifetime') * 60; // 分を秒に変換
            
            if (now()->timestamp - $lastActivity > $timeout) {
                auth()->logout();
                $request->session()->invalidate();
                return redirect()->route('login')
                    ->with('error', 'セッションがタイムアウトしました');
            }
            
            // アクティビティ時刻を更新
            session(['last_activity' => now()->timestamp]);
        }
        
        return $next($request);
    }
}
```

## 3. ゲスト認証（モバイルAPI）

### 3.1 対象ユーザー
- **CUSTOMER**: お客様のスマートフォン
- **HANDY**: ハンディ端末（擬似トークン使用）

### 3.2 認証フロー

#### スマートフォン認証フロー
```
1. QRコード読み取り → 席セッション取得
2. 同意画面表示 → ハンドルキーパー・セキュリティポリシー同意
3. 席セッション取得後 → ゲストトークン自動生成
4. デバイス識別 → device_fingerprint設定
5. APIアクセス時にゲストトークンを使用（Bearer形式）
```

#### ハンディ端末認証フロー
```
1. ハンディ → POS → 擬似トークン生成（handy_proxy_table{N}_{increment}）
2. 擬似フィンガープリント設定（handy_device_fingerprint）
3. POS認証（Bearer pos_system_token）でAPI送信
4. 擬似トークンはDB記録のみ使用（認証機能なし）
```

### 3.3 DBセッション設定
```php
// ゲストセッションTTL管理
'guest_session_ttl' => 1800, // 30分（expires_atカラムで管理）
'guest_cart_ttl' => 1800,    // 30分（expires_atカラムで管理）
'device_fingerprint_retention' => 86400, // 24時間（履歴保持期間）
```

### 3.4 API認証実装
```php
// モバイル API コントローラー
class MobileApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('throttle:api');
    }
    
    protected function authenticateSession(Request $request)
    {
        $qrCode = $request->input('qr_code');
        $session = Session::where('qr_code', $qrCode)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$session) {
            throw new AuthenticationException('無効なQRコードです');
        }
        
        // 一時ユーザー作成またはゲスト認証
        $user = User::createTemporaryCustomer($session);
        $token = $user->createToken('mobile-session')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'session' => $session,
            'expires_at' => $session->expires_at
        ]);
    }
}
```

### 3.5 ゲスト認証実装
```php
// ゲストセッションコントローラー
class GuestSessionController extends Controller
{
    public function create(Request $request)
    {
        $storeId = $request->input('store_id');
        $deviceFingerprint = $request->header('X-Device-Fingerprint');
        $sessionId = $request->input('session_id'); // 必須（QRコード経由）
        
        // ゲストトークン生成
        $guestToken = 'guest_' . Str::random(32);
        
        // DBにゲストセッション保存
        $guestSession = GuestSession::create([
            'token' => $guestToken,
            'device_fingerprint' => $deviceFingerprint,
            'store_id' => $storeId,
            'session_id' => $sessionId,
            'language' => $request->input('language', 'ja'),
            'agreed_policy' => false,
            'expires_at' => now()->addMinutes(30),
            'last_activity' => now()
        ]);
        
        // カート初期化は不要（必要時に作成）
        
        return response()->json([
            'guest_token' => $guestToken,
            'expires_in' => 1800
        ]);
    }
    
    // ゲストセッション自動延長はミドルウェアで実装
    // 各API呼び出し時に自動的にexpires_atを30分延長
}
```

## 4. Laravel Sanctum（POS API）

### 4.1 対象システム
- **POS_SYSTEM**: POSシステム

### 4.2 認証方式
- **トークン有効期限**: 24時間（自動更新機能付き）
- **認証方法**: Bearer Token認証のみ
- **API能力制限**: スコープベース権限管理

### 4.3 POS認証設定
```php
// config/pos.php
return [
    'token_expires' => 86400,     // 24時間 (秒)
    'auto_refresh' => true,       // 自動更新有効
    
    'rate_limits' => [
        'polling' => '60:1',      // 1分間に60回
        'orders' => '30:1',       // 1分間に30回
        'menu_sync' => '10:1',    // 1分間に10回
    ],
];
```

### 4.4 POS認証ミドルウェア
```php
class EnsurePosApiAccess
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        // POS システムユーザーかチェック
        if ($user?->role !== 'pos_system') {
            return response()->json(['error' => 'POS認証が必要です'], 401);
        }
        
        // Bearer Token認証のみで制御
        // IPアドレスによる制限は行わない
        
        return $next($request);
    }
}
```

## 5. 権限管理システム

### 5.1 権限マトリクス
```php
class Permission
{
    const PERMISSIONS = [
        'super_admin' => [
            'system.*',           // 全システム機能
            'stores.*',           // 全店舗管理（新規登録・基本5項目編集）
            'users.*',            // 全ユーザー管理
            'reports.*',          // 全レポート
        ],
        
        'admin' => [
            'store.manage',       // 店舗設定管理
            'menu.view',         // メニュー閲覧（読み取り専用）
            'orders.*',          // 注文管理
            'staff.manage',      // スタッフ管理
            'reports.store',     // 店舗レポート
            'users.manage',      // ユーザー管理
            'settings.manage',   // Web設定管理
        ],
        
        'staff' => [
            'orders.view',       // 注文閲覧
            'orders.update',     // 注文状況更新
            'menu.view',         // メニュー閲覧
        ],
        
        // ゲストユーザーの権限は実装しない - セッション方式で管理
        'guest' => [
            'menu.view',         // メニュー閲覧
            'orders.create',     // 注文作成
            'orders.view',       // 自分の注文閲覧のみ（キャンセル不可）
        ],
        
        'pos_system' => [
            'api.polling',       // ポーリングAPI
            'api.orders',        // 注文API
            'api.orders.cancel', // 注文キャンセルAPI（ハンディ端末経由のみ）
            'api.menu_sync',     // メニュー同期API
            'api.products.*',    // 商品マスター管理（CRUD）
            'api.categories.*',  // カテゴリマスター管理（CRUD）
            'api.options.*',     // オプションマスター管理（CRUD）
            'api.translations.sync', // 翻訳同期API
        ],
    ];
}
```

### 5.2 権限チェック実装
```php
// Gate定義（AuthServiceProvider）
class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Gate::define('manage-store', function (User $user, Store $store) {
            return $user->role === 'super_admin' || 
                   ($user->role === 'admin' && $user->store_id === $store->id);
        });
        
        Gate::define('view-order', function (User $user, Order $order) {
            return $user->role === 'super_admin' ||
                   $user->store_id === $order->store_id ||
                   // ゲストユーザーの場合はguest_tokenで識別
                   (request()->bearerToken() && $order->guest_token === request()->bearerToken());
        });
        
        Gate::define('pos-api-access', function (User $user, string $endpoint) {
            return $user->role === 'pos_system' && 
                   $user->tokenCan('api.' . $endpoint);
        });
    }
}

// Policy使用例
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return Gate::allows('view-order', $order);
    }
    
    public function update(User $user, Order $order): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'staff']) &&
               $user->store_id === $order->store_id;
    }
    
    public function cancel(User $user, Order $order): bool
    {
        // キャンセルはPOSシステム（ハンディ端末経由）のみ許可
        return $user->role === 'pos_system' && 
               $user->tokenCan('api.orders.cancel') &&
               $user->store_id === $order->store_id;
    }
}
```

## 6. セキュリティ対策

### 6.1 レート制限
```php
// RouteServiceProvider
Route::middleware(['api', 'throttle:api'])
    ->prefix('api/v1')
    ->group(base_path('routes/api.php'));

// カスタムレート制限
Route::middleware(['auth:sanctum', 'throttle:pos-api'])
    ->prefix('api/v1/pos')
    ->group(base_path('routes/pos.php'));

// config/cache.php - レート制限設定
'throttle' => [
    'api' => '60:1',           // 一般API：1分間60回
    'pos-api' => '120:1',      // POS API：1分間120回
    'mobile-auth' => '10:1',   // モバイル認証：1分間10回
],
```

### 6.2 CSRF保護
```php
// Web ルートのみCSRF保護
Route::middleware(['web', 'auth', 'verified'])
    ->prefix('admin')
    ->group(base_path('routes/web.php'));

// API ルートはCSRF無効
Route::middleware(['api'])
    ->prefix('api')
    ->group(base_path('routes/api.php'));
```

### 6.3 CORS設定
```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:8080',
        'https://yourdomain.com',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

## 7. セッション管理

### 7.1 QRコード セッション（URLパラメータ方式）
```php
class SessionController extends Controller
{
    public function start(Request $request, $sessionToken)
    {
        // URLパラメータから session_token を取得（QRコード読み取り画面なし）
        $customerCount = $request->input('customer_count', 1);
        
        $session = Session::where('session_id', $sessionToken)
            ->where('expires_at', '>', now())
            ->where('status', 'active')
            ->first();
            
        if (!$session) {
            return response()->json(['error' => '無効または期限切れのQRコードです'], 400);
        }
        
        // セッション開始（初期有効期限3時間）
        $session->update([
            'customer_count' => $customerCount,
            'started_at' => now(),
            'expires_at' => now()->addHours(3), // 初期値3時間
        ]);
        
        // 認証トークン発行
        $user = User::createTemporaryCustomer($session);
        $token = $user->createSessionToken($session);
        
        return response()->json([
            'token' => $token,
            'session_id' => $session->id,
            'expires_at' => $session->expires_at,
            'table_number' => $session->table_number,
        ]);
    }
    
    public function extend(Request $request)
    {
        // POSからの明示的な延長指示のみ受け付ける
        $this->authorize('pos:session:extend');
        
        $sessionId = $request->input('session_id');
        $hours = $request->input('hours', 1); // デフォルト1時間延長
        
        $session = Session::findOrFail($sessionId);
        
        $session->update([
            'expires_at' => now()->addHours($hours),
        ]);
        
        return response()->json([
            'session_id' => $session->id,
            'expires_at' => $session->expires_at,
            'extended_hours' => $hours
        ]);
    }
}
```

### 7.2 セッション クリーンアップ
```php
// app/Console/Commands/CleanupExpiredSessions.php
class CleanupExpiredSessions extends Command
{
    protected $signature = 'sessions:cleanup';
    
    public function handle()
    {
        // 期限切れセッションのステータス更新
        Session::where('expires_at', '<', now())
            ->where('status', 'active')
            ->update(['status' => 'expired']);
            
        // 期限切れから1日経過した一時ユーザー削除
        User::where('is_temporary', true)
            ->where('created_at', '<', now()->subDay())
            ->whereHas('sessions', function($query) {
                $query->where('status', 'expired');
            })
            ->delete();
    }
}
```

## 8. API認証フロー詳細

### 8.1 モバイル認証フロー
```
1. お客様: QRコード読み取り
   ↓
2. アプリ: POST /api/v1/auth/session/start
   Body: { "qr_code": "ABC123", "customer_count": 2 }
   ↓
3. サーバー: 席セッション検証・取得
   ↓
4. アプリ: POST /api/v1/auth/guest/start (席セッション取得後に自動実行)
   Body: { "session_id": 123, "device_fingerprint": "xxx", "store_id": 1 }
   ↓
5. サーバー: ゲストトークン生成・DB保存（guest_sessionsテーブル）
   ↓
6. レスポンス: { "guest_token": "guest_xxx", "expires_in": 1800 }
   ↓
7. 以降のAPI呼び出し: Authorization: Bearer guest_xxx
```

### 8.2 POS認証フロー
```
1. POS管理者: 管理画面でAPIトークン生成
   ↓
2. POS設定: トークンをPOSシステムに設定
   ↓
3. POS→API: Authorization: Bearer xxx (永続トークン)
   ↓
4. サーバー: トークン検証 → IPアドレス確認 → API実行
```

## 9. ログ・監査

### 9.1 認証ログ
```php
// 認証成功・失敗のログ
Log::channel('auth')->info('Login successful', [
    'user_id' => $user->id,
    'role' => $user->role,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);

Log::channel('auth')->warning('Login failed', [
    'email' => $request->input('email'),
    'ip' => $request->ip(),
    'reason' => 'invalid_credentials',
]);
```

### 9.2 API アクセスログ
```php
// API アクセスログミドルウェア
class ApiAccessLogger
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        Log::channel('api')->info('API Access', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => $request->user()?->id,
            'role' => $request->user()?->role,
            'ip' => $request->ip(),
            'status' => $response->status(),
            'response_time' => microtime(true) - LARAVEL_START,
        ]);
        
        return $response;
    }
}
```

## 10. テスト設計

### 10.1 認証テスト
```php
class AuthenticationTest extends TestCase
{
    /** @test */
    public function admin_can_login_with_valid_credentials()
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        
        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
    }
    
    /** @test */
    public function customer_can_authenticate_with_qr_code()
    {
        $session = Session::factory()->active()->create();
        
        $response = $this->postJson('/api/v1/auth/session', [
            'qr_code' => $session->qr_code,
            'customer_count' => 2,
        ]);
        
        $response->assertOk()
            ->assertJsonStructure(['token', 'session_id', 'expires_at']);
    }
    
    /** @test */
    public function admin_session_extends_on_activity()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        
        // 初回アクセス
        $this->get('/admin/dashboard')
            ->assertOk();
            
        // セッションに最終アクティビティが記録される
        $this->assertEquals(now()->timestamp, session('last_activity'));
        
        // 1時間後のアクセス（セッション延長される）
        $this->travel(1)->hours();
        $this->get('/admin/products')
            ->assertOk();
            
        // 最終アクティビティが更新される
        $this->assertEquals(now()->timestamp, session('last_activity'));
        
        // さらに2時間後のアクセス（タイムアウト）
        $this->travel(2)->hours();
        $this->get('/admin/products')
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'セッションがタイムアウトしました');
    }
    
    /** @test */
    public function pos_system_requires_valid_ip_address()
    {
        $posUser = User::factory()->posSystem()->create();
        $token = $posUser->createToken('pos-api')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->from('192.168.999.999') // 無効IP
          ->getJson('/api/v1/pos/orders');
          
        $response->assertStatus(403);
    }
}
```

---

この認証・認可設計により、セキュアで使いやすい多層認証システムを実現します。各認証方式は独立して動作し、適切な権限管理とセキュリティ対策を提供します。