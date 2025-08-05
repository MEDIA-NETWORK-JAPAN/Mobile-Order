# 認証・認可設計書

## 1. 認証・認可概要

### 1.1 認証方式
本システムでは3種類の認証方式を使い分け、各ユーザータイプに最適な認証機能を提供します。

1. **Laravel Breeze**（Web認証）- 管理者・スタッフ用
2. **Laravel Sanctum**（モバイルAPI）- お客様用
3. **Laravel Sanctum**（POS API）- POSシステム用

### 1.2 ユーザー役割
```php
enum UserRole: string 
{
    case SUPER_ADMIN = 'super_admin';    // システム提供者
    case ADMIN = 'admin';                // 店舗管理者  
    case STAFF = 'staff';                // 店舗スタッフ
    case CUSTOMER = 'customer';          // お客様
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
- セッション管理

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
'lifetime' => 120, // 2時間
'expire_on_close' => false,
'encrypt' => true,
'http_only' => true,
'same_site' => 'lax',
```

### 2.4 ミドルウェア構成
```php
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
```

## 3. Laravel Sanctum（モバイルAPI）

### 3.1 対象ユーザー
- **CUSTOMER**: お客様のスマートフォン

### 3.2 認証フロー
```
1. QRコード読み取り → セッション開始
2. 一時トークン発行（session_id ベース）
3. お客様情報登録（任意）
4. APIアクセス時にBearer Token使用
```

### 3.3 トークン設定
```php
// config/sanctum.php
'expiration' => 60 * 24 * 30, // 30日間
'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

'middleware' => [
    'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
    'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    'validate_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
],
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
// User.php Model
class User extends Authenticatable
{
    public static function createTemporaryCustomer(Session $session): self
    {
        return self::create([
            'name' => 'Guest_' . $session->id,
            'email' => 'guest_' . $session->id . '@temp.local',
            'password' => Hash::make(Str::random(32)),
            'role' => 'customer',
            'store_id' => $session->store_id,
            'is_temporary' => true,
        ]);
    }
    
    public function createSessionToken(Session $session): string
    {
        return $this->createToken('session-' . $session->id, [
            'mobile:order', 
            'mobile:menu'
        ])->plainTextToken;
    }
}
```

## 4. Laravel Sanctum（POS API）

### 4.1 対象システム
- **POS_SYSTEM**: POSシステム

### 4.2 認証方式
- **永続トークン**: 有効期限なし
- **IPアドレス制限**: 店舗固有IP許可リスト
- **API能力制限**: スコープベース権限管理

### 4.3 POS認証設定
```php
// config/pos.php
return [
    'allowed_ips' => [
        'store_1' => ['192.168.1.100', '192.168.1.101'],
        'store_2' => ['192.168.2.100', '192.168.2.101'],
    ],
    
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
        
        // IPアドレス制限チェック
        $allowedIps = config('pos.allowed_ips.store_' . $user->store_id, []);
        if (!in_array($request->ip(), $allowedIps)) {
            Log::warning('POS API不正アクセス', [
                'ip' => $request->ip(),
                'user_id' => $user->id,
                'store_id' => $user->store_id
            ]);
            return response()->json(['error' => '許可されていないIPアドレスです'], 403);
        }
        
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
            'stores.*',           // 全店舗管理
            'users.*',            // 全ユーザー管理
            'reports.*',          // 全レポート
        ],
        
        'admin' => [
            'store.manage',       // 店舗設定管理
            'menu.*',            // メニュー管理
            'orders.*',          // 注文管理
            'staff.manage',      // スタッフ管理
            'reports.store',     // 店舗レポート
        ],
        
        'staff' => [
            'orders.view',       // 注文閲覧
            'orders.update',     // 注文状況更新
            'menu.view',         // メニュー閲覧
        ],
        
        'customer' => [
            'menu.view',         // メニュー閲覧
            'orders.create',     // 注文作成
            'orders.own',        // 自分の注文管理
        ],
        
        'pos_system' => [
            'api.polling',       // ポーリングAPI
            'api.orders',        // 注文API
            'api.menu_sync',     // メニュー同期API
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
                   ($user->role === 'customer' && $user->id === $order->customer_id);
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

### 7.1 QRコード セッション
```php
class SessionController extends Controller
{
    public function start(Request $request)
    {
        $qrCode = $request->input('qr_code');
        $customerCount = $request->input('customer_count', 1);
        
        $session = Session::where('qr_code', $qrCode)
            ->where('expires_at', '>', now())
            ->where('status', 'active')
            ->first();
            
        if (!$session) {
            return response()->json(['error' => '無効または期限切れのQRコードです'], 400);
        }
        
        // セッション開始
        $session->update([
            'customer_count' => $customerCount,
            'started_at' => now(),
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
        $session = $request->user()->currentSession();
        
        if ($session && $session->expires_at < now()->addHour()) {
            $session->update([
                'expires_at' => now()->addHours(3), // デフォルト3時間延長
            ]);
        }
        
        return response()->json(['expires_at' => $session->expires_at]);
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
            
        // 期限切れから1週間経過した一時ユーザー削除
        User::where('is_temporary', true)
            ->where('created_at', '<', now()->subWeek())
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
2. アプリ: POST /api/v1/auth/session
   Body: { "qr_code": "ABC123", "customer_count": 2 }
   ↓
3. サーバー: セッション検証 → 一時ユーザー作成 → トークン発行
   ↓
4. レスポンス: { "token": "xxx", "session_id": 1, "expires_at": "2024-01-01 15:00:00" }
   ↓
5. 以降のAPI呼び出し: Authorization: Bearer xxx
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