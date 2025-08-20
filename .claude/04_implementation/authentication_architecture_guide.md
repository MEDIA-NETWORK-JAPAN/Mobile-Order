# Mobile Order System 認証アーキテクチャ実装ガイド

## 📚 目次

- [1. 認証システム概要](#1-認証システム概要)
  - [1.1 4つの認証方式](#11-4つの認証方式)
  - [1.2 システム構成図](#12-システム構成図)
- [2. 認証方式1: Laravel Breeze（管理者Web認証）](#2-認証方式1-laravel-breeze管理者web認証)
  - [2.1 対象ユーザー](#21-対象ユーザー)
  - [2.2 実装仕様](#22-実装仕様)
  - [2.3 セキュリティ設定](#23-セキュリティ設定)
  - [2.4 実装例](#24-実装例)
- [3. 認証方式2: 席セッション認証（第1層）](#3-認証方式2-席セッション認証第1層)
  - [3.1 設計思想](#31-設計思想)
  - [3.2 実装仕様](#32-実装仕様)
  - [3.3 セッションID生成](#33-セッションid生成)
  - [3.4 実装例](#34-実装例)
- [4. 認証方式3: ゲストセッション認証（第2層）](#4-認証方式3-ゲストセッション認証第2層)
  - [4.1 設計思想](#41-設計思想)
  - [4.2 実装仕様](#42-実装仕様)
  - [4.3 Laravel Breezeカスタム認証](#43-laravel-breezeカスタム認証)
  - [4.4 実装例](#44-実装例)
- [5. 認証方式4: Laravel Sanctum（POS API認証）](#5-認証方式4-laravel-sanctumpos-api認証)
  - [5.1 対象システム](#51-対象システム)
  - [5.2 実装仕様](#52-実装仕様)
  - [5.3 トークン管理](#53-トークン管理)
  - [5.4 実装例](#54-実装例)
- [6. 認証フロー詳細](#6-認証フロー詳細)
  - [6.1 お客様の注文フロー](#61-お客様の注文フロー)
  - [6.2 管理者の操作フロー](#62-管理者の操作フロー)
  - [6.3 POSシステム連携フロー](#63-posシステム連携フロー)
- [7. セキュリティ対策](#7-セキュリティ対策)
  - [7.1 共通セキュリティ対策](#71-共通セキュリティ対策)
  - [7.2 認証方式別対策](#72-認証方式別対策)
- [8. 実装チェックリスト](#8-実装チェックリスト)
  - [8.1 設定ファイル](#81-設定ファイル)
  - [8.2 データベース](#82-データベース)
  - [8.3 ミドルウェア](#83-ミドルウェア)

---

## 1. 認証システム概要

### 1.1 4つの認証方式

Mobile Order Systemでは、ユーザータイプとアクセス方法に応じて4つの認証方式を使い分けています：

| 認証方式 | 対象ユーザー | 技術基盤 | 用途 |
|---------|-------------|----------|------|
| **1. Laravel Breeze** | 管理者・スタッフ | セッション/Cookie | Web管理画面 |
| **2. 席セッション認証** | お客様（第1層） | URLパラメータ+DB | 同席者間共有 |
| **3. ゲストセッション認証** | お客様（第2層） | Laravel Breezeカスタム | 個人識別・不正防止 |
| **4. Laravel Sanctum** | POSシステム | Bearer Token | API認証 |

### 1.2 システム構成図

```
【Mobile Order System 認証アーキテクチャ】

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   管理者PC      │    │   お客様スマホ   │    │   POSシステム   │
│                 │    │                 │    │                 │
│ Laravel Breeze  │    │    2層認証      │    │ Laravel Sanctum │
│  (Web認証)      │    │ ①席セッション   │    │  (Bearer Token) │
│                 │    │ ②ゲスト認証     │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                      │                      │
         │                      │                      │
         └──────────────────────┼──────────────────────┘
                                │
                    ┌─────────────────┐
                    │ Laravel Web App │
                    │                 │
                    │  4つの認証Guard │
                    │  - web          │
                    │  - guest        │
                    │  - api          │
                    │  - sanctum      │
                    └─────────────────┘
```

---

## 2. 認証方式1: Laravel Breeze（管理者Web認証）

### 2.1 対象ユーザー

- **SUPER_ADMIN**: システム提供者
- **ADMIN**: 店舗管理者
- **STAFF**: 店舗スタッフ

### 2.2 実装仕様

#### 基本設定
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
```

#### セッション設定
```php
// config/session.php
'lifetime' => 180,           // 3時間
'expire_on_close' => false,  // ブラウザ閉じても継続
'encrypt' => true,           // セッション暗号化
'http_only' => true,         // XSS対策
'same_site' => 'lax',       // CSRF対策
```

### 2.3 セキュリティ設定

#### ミドルウェア構成
```php
// app/Http/Middleware/AdminMiddleware.php
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

// セッション自動延長
class ExtendSessionOnActivity
{
    public function handle($request, Closure $next)
    {
        if ($request->hasSession() && $request->user()) {
            $lastActivity = session('last_activity', now()->timestamp);
            $timeout = config('session.lifetime') * 60;
            
            if (now()->timestamp - $lastActivity > $timeout) {
                auth()->logout();
                $request->session()->invalidate();
                return redirect()->route('login')
                    ->with('error', 'セッションがタイムアウトしました');
            }
            
            session(['last_activity' => now()->timestamp]);
        }
        
        return $next($request);
    }
}
```

### 2.4 実装例

#### ログインコントローラー
```php
// app/Http/Controllers/Auth/LoginController.php
class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // ログイン記録
            auth()->user()->update(['last_login_at' => now()]);
            
            Log::channel('auth')->info('Admin login successful', [
                'user_id' => auth()->id(),
                'role' => auth()->user()->role,
                'ip' => $request->ip(),
            ]);

            return redirect()->intended('/admin/dashboard');
        }

        Log::channel('auth')->warning('Admin login failed', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        return back()->withErrors(['email' => 'ログイン情報が正しくありません。']);
    }
}
```

---

## 3. 認証方式2: 席セッション認証（第1層）

### 3.1 設計思想

**目的**: 同席者間での注文履歴共有を実現する第1層認証

#### 特徴
- **生成元**: POS端末のみ（暗号化セキュリティ強化版）
- **管理場所**: データベース（sessionsテーブル）
- **共有範囲**: 同じテーブルの全利用者
- **有効期限**: 固定QRモード（無期限）/ 都度発行モード（無期限がデフォルト）

### 3.2 実装仕様

#### データベーステーブル
```sql
CREATE TABLE sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(150) NOT NULL UNIQUE COMMENT 'POS生成の暗号化セッションID',
    store_id BIGINT UNSIGNED NOT NULL,
    table_number VARCHAR(50) NOT NULL,
    customer_count INT UNSIGNED NULL,
    status ENUM('active', 'expired', 'completed') NOT NULL DEFAULT 'active',
    expires_at TIMESTAMP NULL COMMENT '固定QRモード時はNULL',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_sessions_session_id (session_id)
);
```

### 3.3 セッションID生成

#### POS端末（Delphi）での生成
```pascal
// POS端末でのセキュリティ強化版セッションID生成
unit SessionIDGenerator;

interface
uses SysUtils, DateUtils;

type TSessionIDGenerator = class
public
    class function GenerateFixedSessionID(TableNumber: Integer): string;
    class function GenerateTempSessionID(TableNumber: Integer): string;
private
    class function SimpleHash(Input: string): string;
    class function GenerateRandomHex(Length: Integer): string;
end;

implementation

class function TSessionIDGenerator.GenerateFixedSessionID(TableNumber: Integer): string;
var
    BaseString, RandomPart: string;
    TimestampPart: string;
begin
    // 固定QRモード用セッションID
    RandomPart := GenerateRandomHex(16);
    TimestampPart := IntToStr(DateTimeToUnix(Now));
    
    BaseString := Format('FIXED_%d_%s_%s', [TableNumber, TimestampPart, RandomPart]);
    Result := 'SESSION_POS_' + SimpleHash(BaseString);
end;

class function TSessionIDGenerator.GenerateTempSessionID(TableNumber: Integer): string;
var
    BaseString, RandomPart: string;
    TimestampPart: string;
begin
    // 都度発行モード用セッションID
    RandomPart := GenerateRandomHex(12);
    TimestampPart := IntToStr(DateTimeToUnix(Now));
    
    BaseString := Format('TEMP_%d_%s_%s', [TableNumber, TimestampPart, RandomPart]);
    Result := 'SESSION_POS_' + SimpleHash(BaseString);
end;
```

### 3.4 実装例

#### セッション検証ミドルウェア
```php
// app/Http/Middleware/ValidateSessionToken.php
class ValidateSessionToken
{
    public function handle($request, Closure $next)
    {
        $sessionToken = $request->route('session_token') ?? $request->input('session');
        
        if (!$sessionToken) {
            return redirect('/error/invalid-session');
        }

        $session = Session::where('session_id', $sessionToken)
            ->where('status', 'active')
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$session) {
            return redirect('/error/invalid-session');
        }

        // セッション情報をリクエストに保存
        $request->merge(['seat_session' => $session]);
        
        return $next($request);
    }
}
```

---

## 4. 認証方式3: ゲストセッション認証（第2層）

### 4.1 設計思想

**目的**: 個人識別・不正アクセス防止・端末特定を行う第2層認証

#### 特徴
- **生成**: スマホアクセス時に自動生成
- **管理場所**: データベース（guest_sessionsテーブル）
- **識別子**: guest_token + device_fingerprint
- **有効期限**: 30分（アクティビティで自動延長）

### 4.2 実装仕様

#### Laravel Breezeカスタムガード
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    
    'guest' => [
        'driver' => 'session',
        'provider' => 'guest_sessions',
    ],
],

'providers' => [
    'guest_sessions' => [
        'driver' => 'eloquent',
        'model' => App\Models\GuestSession::class,
    ],
],
```

#### データベーステーブル
```sql
CREATE TABLE guest_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    device_fingerprint VARCHAR(255) NOT NULL,
    store_id BIGINT UNSIGNED NOT NULL,
    
    -- Laravel Breeze Authenticatable必須カラム
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NULL,
    remember_token VARCHAR(100) NULL,
    
    -- ゲスト専用カラム
    guest_token VARCHAR(64) NOT NULL UNIQUE,
    language CHAR(2) DEFAULT 'ja',
    agreed_policy BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- 視覚的識別情報
    identifier_icon VARCHAR(10) NOT NULL DEFAULT '🐶',
    identifier_color VARCHAR(7) NOT NULL DEFAULT '#FF6B6B',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_guest_device (session_id, device_fingerprint)
);
```

### 4.3 Laravel Breezeカスタム認証

#### GuestSessionモデル
```php
// app/Models/GuestSession.php
class GuestSession extends Model implements Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'session_id', 'device_fingerprint', 'store_id', 'email',
        'guest_token', 'language', 'agreed_policy', 'expires_at',
        'last_activity', 'identifier_icon', 'identifier_color'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_activity' => 'datetime',
        'agreed_policy' => 'boolean',
    ];

    // Authenticatable インターフェース実装
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    public function getAuthPassword()
    {
        return null; // パスワード認証は使用しない
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
```

### 4.4 実装例

#### ゲスト登録コントローラー
```php
// app/Http/Controllers/GuestSessionController.php
class GuestSessionController extends Controller
{
    public function store(Request $request)
    {
        $seatSession = $request->seat_session;
        
        $request->validate([
            'device_fingerprint' => 'required|string',
            'language' => 'nullable|string|in:ja,en,zh-CN,zh-TW,ko'
        ]);

        // 既存ゲストセッション確認
        $guestSession = GuestSession::where('session_id', $seatSession->id)
            ->where('device_fingerprint', $request->device_fingerprint)
            ->where('expires_at', '>', now())
            ->first();

        if (!$guestSession) {
            $guestSession = GuestSession::create([
                'session_id' => $seatSession->id,
                'device_fingerprint' => $request->device_fingerprint,
                'store_id' => $seatSession->store_id,
                'email' => $request->device_fingerprint . '@guest.local',
                'guest_token' => Str::random(64),
                'language' => $request->input('language', 'ja'),
                'expires_at' => now()->addMinutes(30),
                'last_activity' => now(),
                'identifier_icon' => $this->generateRandomIcon(),
                'identifier_color' => $this->generateRandomColor(),
            ]);
        }

        // Laravel Breezeカスタム認証でログイン
        Auth::guard('guest')->login($guestSession);

        return redirect()->route('guest.agreement');
    }

    private function generateRandomIcon(): string
    {
        $icons = ['🐶', '🐱', '🐰', '🐼', '🦊', '🐸', '🐯', '🐨'];
        return $icons[array_rand($icons)];
    }

    private function generateRandomColor(): string
    {
        $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57', '#FF9FF3', '#54A0FF', '#5F27CD'];
        return $colors[array_rand($colors)];
    }
}
```

---

## 5. 認証方式4: Laravel Sanctum（POS API認証）

### 5.1 対象システム

- **POS_SYSTEM**: POSシステム（Delphi + FireBird）

### 5.2 実装仕様

#### Sanctum設定
```php
// config/sanctum.php
return [
    'stateful' => [
        'localhost',
        'localhost:3000',
        '127.0.0.1',
        '127.0.0.1:8000',
        '::1',
    ],

    'guard' => ['web'],

    'expiration' => 1440, // 24時間（分）

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

### 5.3 トークン管理

#### POSユーザー作成とトークン発行
```php
// app/Http/Controllers/Admin/POSTokenController.php
class POSTokenController extends Controller
{
    public function createToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'store_id' => 'required|exists:stores,id',
        ]);

        // POSシステムユーザー作成
        $posUser = User::create([
            'name' => 'POS System - ' . $request->name,
            'email' => 'pos.' . Str::random(10) . '@system.local',
            'password' => Hash::make(Str::random(32)),
            'role' => 'pos_system',
            'store_id' => $request->store_id,
            'is_active' => true,
        ]);

        // Sanctum API トークン発行
        $token = $posUser->createToken('pos-api', [
            'api.polling',
            'api.orders',
            'api.orders.cancel',
            'api.menu_sync',
            'api.products.*',
            'api.categories.*',
            'api.options.*',
            'api.translations.sync',
        ]);

        return response()->json([
            'token' => $token->plainTextToken,
            'user_id' => $posUser->id,
            'expires_at' => now()->addDay(),
        ]);
    }
}
```

### 5.4 実装例

#### POS認証ミドルウェア
```php
// app/Http/Middleware/EnsurePosApiAccess.php
class EnsurePosApiAccess
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if ($user?->role !== 'pos_system') {
            return response()->json(['error' => 'POS認証が必要です'], 401);
        }

        // トークンの自動更新チェック
        $token = $user->currentAccessToken();
        if ($token && $token->created_at->diffInHours(now()) > 20) {
            // 20時間経過後は新しいトークンを発行
            $newToken = $user->createToken('pos-api-renewed', $token->abilities);
            $token->delete();
            
            return response()->json([
                'message' => 'Token renewed',
                'new_token' => $newToken->plainTextToken
            ], 200);
        }

        return $next($request);
    }
}
```

---

## 6. 認証フロー詳細

### 6.1 お客様の注文フロー

```
1. QRコード読み取り
   ↓
2. 席セッション認証（第1層）
   GET /order?session=SESSION_POS_XXXXX
   ↓
3. デバイス識別登録
   POST /guest/register
   Body: { device_fingerprint, language }
   ↓
4. ゲストセッション認証（第2層）
   Laravel Breezeカスタム認証
   Auth::guard('guest')->login()
   ↓
5. 同意画面
   ハンドルキーパー・セキュリティポリシー
   ↓
6. メニュー画面
   セッションCookie認証（CSRF保護付き）
```

### 6.2 管理者の操作フロー

```
1. ログイン画面
   POST /login
   Body: { email, password, remember }
   ↓
2. Laravel Breeze認証
   Auth::attempt()
   ↓
3. セッション開始
   Cookie: laravel_session
   ↓
4. 管理画面アクセス
   Middleware: auth, admin
   ↓
5. アクティビティ監視
   自動セッション延長（3時間）
```

### 6.3 POSシステム連携フロー

```
1. トークン発行（管理画面）
   POST /admin/pos/tokens
   ↓
2. POS設定
   Bearer Token設定
   ↓
3. API認証
   Authorization: Bearer xxxxx
   ↓
4. Sanctum認証
   User::tokenCan('api.polling')
   ↓
5. API実行
   GET /api/v1/pos/changes
```

---

## 7. セキュリティ対策

### 7.1 共通セキュリティ対策

#### レート制限
```php
// config/cache.php
'throttle' => [
    'api' => '60:1',           // 一般API：1分間60回
    'pos-api' => '120:1',      // POS API：1分間120回
    'mobile-auth' => '10:1',   // モバイル認証：1分間10回
],
```

#### CORS設定
```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:8080',
        'https://yourdomain.com',
    ],
    'supports_credentials' => true,
];
```

### 7.2 認証方式別対策

#### Laravel Breeze
- CSRF トークン必須
- セッション暗号化
- HttpOnly Cookie
- 自動セッション延長

#### 席セッション認証
- POS端末のみでのID生成
- URLパラメータ暗号化
- 有効期限管理

#### ゲストセッション認証
- デバイスフィンガープリント
- 30分TTL（自動延長）
- 視覚的識別

#### Laravel Sanctum
- Bearer Token認証
- スコープベース権限管理
- 24時間TTL（自動更新）

---

## 8. 実装チェックリスト

### 8.1 設定ファイル

- [ ] `config/auth.php` - 4つのガード設定
- [ ] `config/session.php` - セッション設定
- [ ] `config/sanctum.php` - Sanctum設定
- [ ] `config/cors.php` - CORS設定

### 8.2 データベース

- [ ] `users` テーブル - 管理者・POS用
- [ ] `sessions` テーブル - 席セッション
- [ ] `guest_sessions` テーブル - ゲスト認証
- [ ] `personal_access_tokens` テーブル - Sanctum

### 8.3 ミドルウェア

- [ ] `AdminMiddleware` - 管理者認証
- [ ] `ValidateSessionToken` - 席セッション検証
- [ ] `EnsurePosApiAccess` - POS API認証
- [ ] `ExtendSessionOnActivity` - セッション延長

---

この4つの認証方式により、Mobile Order Systemは各ユーザータイプに最適化されたセキュアな認証機能を提供します。