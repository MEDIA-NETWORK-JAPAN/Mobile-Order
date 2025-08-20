# Laravel Breeze & Sanctum ベストプラクティス

## 📚 目次
- [1. Laravel Breezeの基礎](#1-laravel-breezeの基礎)
- [2. Laravel Sanctumの基礎](#2-laravel-sanctumの基礎)
- [3. カスタムモデルでのBreeze実装](#3-カスタムモデルでのbreeze実装)
- [4. セキュリティベストプラクティス](#4-セキュリティベストプラクティス)
- [5. セッション管理の最適化](#5-セッション管理の最適化)

---

## 1. Laravel Breezeの基礎

### 1.1 Breezeとは
Laravel Breezeは、Laravelの認証機能の最小限の実装を提供する公式パッケージです。

#### 主な機能
- ログイン/ログアウト
- ユーザー登録
- パスワードリセット
- メール認証
- パスワード確認
- セッション管理

### 1.2 セッション認証の仕組み
```php
// セッションベースの認証フロー
1. ユーザーがログインフォームに認証情報を送信
2. Laravelが認証情報を検証
3. 成功時、セッションIDをCookieに保存
4. 以降のリクエストでセッションIDから認証状態を確認
```

### 1.3 Breezeのファイル構成
```
app/
├── Http/
│   ├── Controllers/Auth/    # 認証コントローラー
│   └── Requests/Auth/       # フォームリクエスト
├── Models/
│   └── User.php            # 認証モデル
├── Providers/
│   └── RouteServiceProvider.php  # ルート設定
resources/
├── views/auth/             # 認証画面
routes/
├── auth.php               # 認証ルート
```

---

## 2. Laravel Sanctumの基礎

### 2.1 Sanctumとは
Laravel SanctumはSPA（Single Page Application）やモバイルアプリケーション向けの軽量な認証パッケージです。

#### 2つの認証方式
1. **SPAセッション認証**: Cookieベースのセッション認証
2. **APIトークン認証**: Personal Access Token による認証

### 2.2 トークン認証の仕組み
```php
// トークンベースの認証フロー
1. ユーザーが認証情報を送信
2. サーバーがトークンを発行
3. クライアントがトークンをストレージに保存
4. APIリクエスト時にBearerトークンとして送信
5. サーバーがトークンを検証
```

### 2.3 Personal Access Tokens テーブル
```sql
CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,  -- モデルクラス名
    tokenable_id BIGINT UNSIGNED NOT NULL,  -- モデルID
    name VARCHAR(255) NOT NULL,             -- トークン名
    token VARCHAR(64) NOT NULL,             -- ハッシュ化トークン
    abilities TEXT NULL,                    -- 権限スコープ
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (token),
    INDEX (tokenable_type, tokenable_id)
);
```

---

## 3. カスタムモデルでのBreeze実装

### 3.1 GuestSessionモデルでBreezeセッション認証

#### Step 1: GuestSessionモデルの準備
```php
// app/Models/GuestSession.php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class GuestSession extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'session_id',
        'device_fingerprint',
        'store_id',
        'language',
        'agreed_policy',
        'expires_at',
        'last_activity'
    ];

    protected $casts = [
        'agreed_policy' => 'boolean',
        'expires_at' => 'datetime',
        'last_activity' => 'datetime'
    ];

    // Breezeで必要なメソッドをオーバーライド
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
        // パスワード認証は使用しない
        return null;
    }

    public function getRememberTokenName()
    {
        // Remember Me機能は使用しない
        return null;
    }
}
```

#### Step 2: 認証ガードの設定
```php
// config/auth.php
return [
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        
        // ゲスト用セッション認証ガード
        'guest' => [
            'driver' => 'session',
            'provider' => 'guest_sessions',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        
        // GuestSessionプロバイダー
        'guest_sessions' => [
            'driver' => 'eloquent',
            'model' => App\Models\GuestSession::class,
        ],
    ],
];
```

#### Step 3: カスタム認証コントローラー
```php
// app/Http/Controllers/Auth/GuestSessionController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GuestSession;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestSessionController extends Controller
{
    /**
     * ゲストセッション開始（QRコード読み取り後）
     */
    public function store(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'device_fingerprint' => 'required|string',
        ]);

        // 席セッション確認
        $session = Session::where('session_id', $request->session_id)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            return back()->withErrors(['session_id' => 'QRコードが無効です']);
        }

        // 既存のゲストセッションを確認
        $guestSession = GuestSession::where('session_id', $session->id)
            ->where('device_fingerprint', $request->device_fingerprint)
            ->where('expires_at', '>', now())
            ->first();

        if (!$guestSession) {
            // 新規作成
            $guestSession = GuestSession::create([
                'session_id' => $session->id,
                'device_fingerprint' => $request->device_fingerprint,
                'store_id' => $session->store_id,
                'language' => $request->input('language', 'ja'),
                'agreed_policy' => false,
                'expires_at' => now()->addMinutes(30),
                'last_activity' => now()
            ]);
        }

        // Breezeのセッション認証でログイン
        Auth::guard('guest')->login($guestSession);

        return redirect()->intended('/menu');
    }

    /**
     * ログアウト
     */
    public function destroy(Request $request)
    {
        Auth::guard('guest')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
```

### 3.2 ルート設定
```php
// routes/web.php
use App\Http\Controllers\Auth\GuestSessionController;

// ゲスト認証ルート
Route::middleware('guest:guest')->group(function () {
    Route::get('/order', [GuestSessionController::class, 'create'])
        ->name('guest.login');
    Route::post('/order', [GuestSessionController::class, 'store']);
});

Route::middleware('auth:guest')->group(function () {
    Route::get('/menu', [MenuController::class, 'index'])
        ->name('menu');
    Route::post('/logout', [GuestSessionController::class, 'destroy'])
        ->name('guest.logout');
});
```

### 3.3 ミドルウェアのカスタマイズ
```php
// app/Http/Middleware/Authenticate.php
protected function redirectTo($request)
{
    if (!$request->expectsJson()) {
        // ガードによってリダイレクト先を変更
        if ($request->is('admin/*')) {
            return route('login');
        }
        
        if ($request->is('menu/*') || $request->is('cart/*')) {
            return route('guest.login');
        }
        
        return route('login');
    }
}
```

---

## 4. セキュリティベストプラクティス

### 4.1 セッション設定の最適化
```php
// config/session.php
return [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => 120,  // 管理者: 120分
    'expire_on_close' => false,
    'encrypt' => true,  // セッションデータ暗号化
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION', null),
    'table' => 'sessions',
    'store' => env('SESSION_STORE', null),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'laravel_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE_COOKIE', true),  // HTTPS必須
    'http_only' => true,  // XSS対策
    'same_site' => 'lax',  // CSRF対策
];
```

### 4.2 ゲストセッション用の個別設定
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'guest_web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \App\Http\Middleware\StartGuestSession::class,  // カスタムセッション
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### 4.3 カスタムセッションミドルウェア
```php
// app/Http/Middleware/StartGuestSession.php
namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession;

class StartGuestSession extends StartSession
{
    protected function getSessionLifetimeInMinutes()
    {
        // ゲストセッションは30分
        return 30;
    }

    protected function sessionConfigured()
    {
        // ゲスト用の設定を適用
        config([
            'session.cookie' => 'guest_session',
            'session.lifetime' => 30,
        ]);
        
        return parent::sessionConfigured();
    }
}
```

### 4.4 CSRF保護の設定
```php
// app/Http/Middleware/VerifyCsrfToken.php
class VerifyCsrfToken extends Middleware
{
    /**
     * CSRF検証を除外するURI
     */
    protected $except = [
        'api/*',  // API呼び出しは除外
        'pos/*',  // POS API呼び出しは除外
    ];

    /**
     * トークンが一致するか判定
     */
    protected function tokensMatch($request)
    {
        // ゲストセッションの場合は別のトークン名を使用
        if ($request->is('menu/*') || $request->is('cart/*')) {
            $token = $request->input('_guest_token') ?: $request->header('X-GUEST-TOKEN');
            return hash_equals($request->session()->token(), $token);
        }

        return parent::tokensMatch($request);
    }
}
```

### 4.5 レート制限
```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting()
{
    RateLimiter::for('guest', function (Request $request) {
        // デバイスフィンガープリントでレート制限
        $fingerprint = $request->header('X-Device-Fingerprint');
        return Limit::perMinute(60)->by($fingerprint ?: $request->ip());
    });

    RateLimiter::for('guest-auth', function (Request $request) {
        // ゲスト認証は厳しく制限
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

---

## 5. セッション管理の最適化

### 5.1 セッションテーブルの最適化
```sql
-- sessionsテーブル（Laravel標準）
CREATE TABLE sessions (
    id VARCHAR(255) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL,
    PRIMARY KEY (id),
    INDEX sessions_user_id_index (user_id),
    INDEX sessions_last_activity_index (last_activity)
) ENGINE=InnoDB;

-- ゲスト用に拡張
ALTER TABLE sessions 
ADD COLUMN guard VARCHAR(50) DEFAULT 'web' AFTER user_id,
ADD INDEX sessions_guard_index (guard);
```

### 5.2 セッションガベージコレクション
```php
// app/Console/Commands/CleanupSessions.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupSessions extends Command
{
    protected $signature = 'sessions:cleanup';
    protected $description = 'Clean up expired sessions';

    public function handle()
    {
        // 期限切れのセッションを削除
        $expired = now()->subMinutes(120)->timestamp;
        
        DB::table('sessions')
            ->where('guard', 'web')
            ->where('last_activity', '<', $expired)
            ->delete();

        // ゲストセッションは30分で削除
        $guestExpired = now()->subMinutes(30)->timestamp;
        
        DB::table('sessions')
            ->where('guard', 'guest')
            ->where('last_activity', '<', $guestExpired)
            ->delete();

        // guest_sessionsテーブルもクリーンアップ
        DB::table('guest_sessions')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info('Sessions cleaned up successfully');
    }
}
```

### 5.3 セッション延長の実装
```php
// app/Http/Middleware/ExtendGuestSession.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ExtendGuestSession
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user('guest')) {
            $guestSession = $request->user('guest');
            
            // アクティビティで自動延長
            if ($guestSession->last_activity < now()->subMinutes(5)) {
                $guestSession->update([
                    'expires_at' => now()->addMinutes(30),
                    'last_activity' => now()
                ]);
            }
        }

        return $next($request);
    }
}
```

### 5.4 セッション同時接続制限
```php
// app/Http/Middleware/SingleSessionPerDevice.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SingleSessionPerDevice
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('guest')->check()) {
            $user = Auth::guard('guest')->user();
            $currentSessionId = session()->getId();
            
            // 同じデバイスの他のセッションを無効化
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('guard', 'guest')
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        return $next($request);
    }
}
```

---

## 📝 実装チェックリスト

### Breeze導入時の確認事項
- [ ] 認証用のテーブルが準備されているか
- [ ] Authenticatableを継承したモデルがあるか
- [ ] Guard/Provider設定が正しいか
- [ ] ルート設定が適切か
- [ ] ミドルウェアが正しく適用されているか
- [ ] CSRF保護が有効か
- [ ] セッション設定が最適化されているか

### Sanctum導入時の確認事項
- [ ] personal_access_tokensテーブルが作成されているか
- [ ] HasApiTokensトレイトが適用されているか
- [ ] Guard設定でsanctumドライバーが指定されているか
- [ ] API認証ミドルウェアが適用されているか
- [ ] トークンのスコープが適切に設定されているか

### セキュリティチェック
- [ ] HTTPS通信が強制されているか
- [ ] セッションCookieがhttpOnlyか
- [ ] CSRFトークンが正しく検証されているか
- [ ] レート制限が適用されているか
- [ ] セッションハイジャック対策があるか
- [ ] XSS対策が実装されているか

---

## 🚨 よくある落とし穴と対策

### 1. セッション競合
**問題**: 複数のガードでセッションが競合する
**対策**: セッションCookie名を分ける

### 2. CSRF Token Mismatch
**問題**: ゲストセッションでCSRFエラーが頻発
**対策**: カスタムCSRF検証ロジックを実装

### 3. セッション期限切れ
**問題**: ゲストがメニューを見ている間にセッション切れ
**対策**: アクティビティベースの自動延長実装

### 4. Remember Me問題
**問題**: ゲストにRemember Me機能は不要
**対策**: getRememberTokenName()でnullを返す

### 5. パスワードなし認証
**問題**: ゲストはパスワード認証しない
**対策**: カスタム認証ロジックの実装

---

**作成日**: 2025年8月16日  
**対象**: Mobile Order System認証システム