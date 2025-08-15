# エラーハンドリング設計書

## 📚 目次

- [1. エラーハンドリング方針](#1-エラーハンドリング方針)
  - [1.1 基本方針](#11-基本方針)
  - [1.2 エラー分類](#12-エラー分類)
- [2. エラーコード体系](#2-エラーコード体系)
  - [2.1 エラーコード構造](#21-エラーコード構造)
  - [2.2 カテゴリ定義](#22-カテゴリ定義)
  - [2.3 主要エラーコード一覧](#23-主要エラーコード一覧)
- [3. エラーメッセージ管理](#3-エラーメッセージ管理)
  - [3.1 メッセージ定義](#31-メッセージ定義)
  - [3.2 多言語対応実装](#32-多言語対応実装)
- [4. エラーハンドリング実装](#4-エラーハンドリング実装)
  - [4.1 カスタム例外クラス](#41-カスタム例外クラス)
  - [4.2 グローバルエラーハンドラー](#42-グローバルエラーハンドラー)
- [5. 在庫切れエラー処理](#5-在庫切れエラー処理)
  - [5.1 在庫チェックとエラー処理](#51-在庫チェックとエラー処理)
  - [5.2 フロントエンド処理（Livewire）](#52-フロントエンド処理livewire)
- [6. POS連携エラー処理](#6-pos連携エラー処理)
  - [6.1 POS連携エラーハンドリング](#61-pos連携エラーハンドリング)
  - [6.2 POS同期リトライ処理](#62-pos同期リトライ処理)
- [7. エラーログ管理](#7-エラーログ管理)
  - [7.1 ログ設定](#71-ログ設定)
  - [7.2 エラーログ記録](#72-エラーログ記録)
- [8. フロントエンドエラー表示](#8-フロントエンドエラー表示)
  - [8.1 Livewireコンポーネント](#81-livewireコンポーネント)
  - [8.2 Mary UIエラー表示](#82-mary-uiエラー表示)
- [9. テスト設計](#9-テスト設計)
  - [9.1 エラーハンドリングテスト](#91-エラーハンドリングテスト)

---

## 1. エラーハンドリング方針

### 1.1 基本方針
- **ユーザーフレンドリー**: 技術的な詳細ではなく、ユーザーが理解できるメッセージを表示
- **多言語対応**: 全てのエラーメッセージを5言語（日本語、英語、中国語繁体字、中国語簡体字、韓国語）で提供
- **一貫性**: 同じ種類のエラーは同じ形式で処理
- **ログ記録**: 全てのエラーを適切なレベルで記録し、1年間保持
- **POS連携考慮**: POS連携エラーは「受付中」ステータスとして管理

### 1.2 エラー分類
1. **バリデーションエラー**: 入力値の検証エラー
2. **認証エラー**: 認証・認可に関するエラー
3. **ビジネスロジックエラー**: 業務ルール違反
4. **システムエラー**: サーバー内部エラー
5. **外部連携エラー**: POS連携、決済システム等の外部サービスエラー

## 2. エラーコード体系

### 2.1 エラーコード構造
```
[カテゴリ]-[サブカテゴリ]-[連番]
例: VAL-ORD-001 (バリデーション-注文-001)
```

### 2.2 カテゴリ定義
- **VAL**: バリデーションエラー
- **AUTH**: 認証・認可エラー
- **BIZ**: ビジネスロジックエラー
- **SYS**: システムエラー
- **EXT**: 外部連携エラー

### 2.3 主要エラーコード一覧
```php
// app/Enums/ErrorCode.php
enum ErrorCode: string
{
    // バリデーションエラー
    case VAL_ORD_001 = 'VAL-ORD-001'; // 注文数量が無効
    case VAL_ORD_002 = 'VAL-ORD-002'; // 必須オプション未選択
    case VAL_ORD_003 = 'VAL-ORD-003'; // 最大注文数量超過
    
    // 認証エラー
    case AUTH_SES_001 = 'AUTH-SES-001'; // セッション期限切れ
    case AUTH_SES_002 = 'AUTH-SES-002'; // 無効なQRコード
    case AUTH_POL_001 = 'AUTH-POL-001'; // ポリシー未同意
    case AUTH_API_001 = 'AUTH-API-001'; // APIトークン無効
    
    // ビジネスロジックエラー
    case BIZ_STK_001 = 'BIZ-STK-001'; // 在庫切れ
    case BIZ_STK_002 = 'BIZ-STK-002'; // 提供時間外
    case BIZ_ORD_001 = 'BIZ-ORD-001'; // 注文受付時間外
    
    // システムエラー
    case SYS_GEN_001 = 'SYS-GEN-001'; // 一般的なシステムエラー
    case SYS_DB_001 = 'SYS-DB-001'; // データベース接続エラー
    case SYS_POS_001 = 'SYS-POS-001'; // POSシステム障害（通信混雑）
    case SYS_POS_002 = 'SYS-POS-002'; // POSシステム障害（システムエラー）
    case SYS_REC_001 = 'SYS-REC-001'; // システム復旧中
    
    // 外部連携エラー
    case EXT_POS_001 = 'EXT-POS-001'; // POS連携エラー
    case EXT_PAY_001 = 'EXT-PAY-001'; // 決済システムエラー
}
```

## 3. エラーメッセージ管理

### 3.1 メッセージ定義
```php
// resources/lang/ja/errors.php
return [
    'VAL-ORD-001' => '注文数量は1個以上を指定してください。',
    'VAL-ORD-002' => '必須のオプションを選択してください。',
    'VAL-ORD-003' => '一度に注文できる数量は10個までです。',
    'SYS-GEN-001' => 'システムエラーが発生しました。しばらく待ってから再度お試しください。',
    'SYS-POS-001' => '現在通信が混み合っています。しばらくお待ちください。',
    'SYS-POS-002' => 'システムエラーが発生しています。システムの復旧までしばらくお待ちください。お急ぎの方は従業員をお呼びください。',
    'SYS-REC-001' => 'システムが復旧中です。しばらくお待ちください。',
    'BIZ-STK-001' => '申し訳ございません。この商品は現在売り切れです。',
    'BIZ-STK-002' => 'この商品は現在の時間帯では提供しておりません。',
    'SYS-GEN-001' => 'システムエラーが発生しました。しばらく待ってから再度お試しください。',
];

// resources/lang/en/errors.php
return [
    'VAL-ORD-001' => 'Please specify a quantity of 1 or more.',
    'VAL-ORD-002' => 'Please select required options.',
    'VAL-ORD-003' => 'You can order up to 10 items at once.',
    'AUTH-SES-001' => 'Your session has expired. Please scan the QR code again.',
    'AUTH-SES-002' => 'Invalid QR code. Please contact store staff.',
    'AUTH-POL-001' => 'You must agree to the terms of service to use this service.',
    'BIZ-STK-001' => 'Sorry, this item is currently out of stock.',
    'BIZ-STK-002' => 'This item is not available at this time.',
    'SYS-GEN-001' => 'A system error occurred. Please try again later.',
];
```

### 3.2 多言語対応実装
```php
// app/Services/ErrorMessageService.php
class ErrorMessageService
{
    public function getMessage(string $errorCode, string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $key = "errors.{$errorCode}";
        
        // 翻訳が存在しない場合は英語にフォールバック
        $message = trans($key, [], $locale);
        if ($message === $key) {
            $message = trans($key, [], 'en');
        }
        
        return $message;
    }
    
    public function getMessagesForAllLocales(string $errorCode): array
    {
        $locales = ['ja', 'en', 'zh-TW', 'zh-CN', 'ko'];
        $messages = [];
        
        foreach ($locales as $locale) {
            $messages[$locale] = $this->getMessage($errorCode, $locale);
        }
        
        return $messages;
    }
}
```

## 4. エラーハンドリング実装

### 4.1 カスタム例外クラス
```php
// app/Exceptions/BusinessException.php
class BusinessException extends Exception
{
    protected string $errorCode;
    protected array $context;
    
    public function __construct(
        string $errorCode,
        array $context = [],
        ?Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->context = $context;
        
        $message = app(ErrorMessageService::class)->getMessage($errorCode);
        parent::__construct($message, 0, $previous);
    }
    
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
}

// app/Exceptions/ValidationException.php
class ValidationException extends BusinessException
{
    protected array $errors;
    
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('VAL-GEN-001', ['errors' => $errors]);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

### 4.2 グローバルエラーハンドラー
```php
// app/Exceptions/Handler.php
class Handler extends ExceptionHandler
{
    private ErrorMessageService $errorMessageService;
    
    public function __construct(
        Container $container,
        ErrorMessageService $errorMessageService
    ) {
        parent::__construct($container);
        $this->errorMessageService = $errorMessageService;
    }
    
    public function render($request, Throwable $exception)
    {
        // APIリクエストの場合
        if ($request->expectsJson()) {
            return $this->renderJsonResponse($exception, $request);
        }
        
        // Webリクエストの場合
        return $this->renderWebResponse($exception, $request);
    }
    
    protected function renderJsonResponse(Throwable $exception, Request $request)
    {
        $locale = $request->header('Accept-Language', 'ja');
        
        if ($exception instanceof BusinessException) {
            $errorCode = $exception->getErrorCode();
            $message = $this->errorMessageService->getMessage($errorCode, $locale);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $message,
                    'details' => $exception->getContext(),
                ],
            ], $this->getStatusCode($exception));
        }
        
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $this->errorMessageService->getMessage('VAL-GEN-001', $locale),
                    'details' => $exception->getErrors(),
                ],
            ], 422);
        }
        
        // システムエラー
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'SYS-GEN-001',
                'message' => $this->errorMessageService->getMessage('SYS-GEN-001', $locale),
                'request_id' => $request->header('X-Request-ID'),
            ],
        ], 500);
    }
    
    protected function renderWebResponse(Throwable $exception, Request $request)
    {
        if ($exception instanceof BusinessException) {
            $errorCode = $exception->getErrorCode();
            $message = $this->errorMessageService->getMessage($errorCode);
            
            // フラッシュメッセージとして保存
            session()->flash('error', $message);
            
            return back()->withInput();
        }
        
        return parent::render($request, $exception);
    }
    
    protected function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof BusinessException) {
            $errorCode = $exception->getErrorCode();
            
            // エラーコードに基づいてHTTPステータスコードを決定
            if (str_starts_with($errorCode, 'VAL-')) {
                return 422;
            }
            if (str_starts_with($errorCode, 'AUTH-')) {
                return 401;
            }
            if (str_starts_with($errorCode, 'BIZ-')) {
                return 400;
            }
        }
        
        return 500;
    }
}
```

## 5. 在庫切れエラー処理

### 5.1 在庫チェックとエラー処理
```php
// app/Services/CartService.php
class CartService
{
    public function addToCart(int $menuItemId, int $quantity): void
    {
        $menuItem = Product::find($menuItemId);
        
        // 在庫チェック
        if (!$menuItem->is_available) {
            throw new BusinessException('BIZ-STK-001', [
                'product_id' => $menuItemId,
                'menu_item_name' => $menuItem->name,
            ]);
        }
        
        // カートに追加処理...
    }
    
    public function validateCart(): array
    {
        $errors = [];
        $itemsToRemove = [];
        
        foreach ($this->getCartItems() as $index => $item) {
            $menuItem = Product::find($item['product_id']);
            
            if (!$menuItem || !$menuItem->is_available) {
                $itemsToRemove[] = $index;
                $errors[] = [
                    'item' => $item['name'],
                    'reason' => 'out_of_stock',
                ];
            }
        }
        
        // 在庫切れ商品をカートから削除
        foreach ($itemsToRemove as $index) {
            $this->removeFromCart($index);
        }
        
        return $errors;
    }
}
```

### 5.2 フロントエンド処理（Livewire）
```php
// app/Livewire/Customer/CartComponent.php
class CartComponent extends Component
{
    public function checkout()
    {
        $cartService = app(CartService::class);
        $errors = $cartService->validateCart();
        
        if (!empty($errors)) {
            // 在庫切れメッセージを表示
            $itemNames = array_column($errors, 'item');
            $message = __('errors.BIZ-STK-001') . ': ' . implode(', ', $itemNames);
            
            session()->flash('error', $message);
            $this->emit('cartUpdated');
            return;
        }
        
        // 注文処理を続行...
    }
}
```

## 6. POS連携エラー処理

### 6.1 POS連携エラーハンドリング
```php
// app/Services/PosIntegrationService.php
class PosIntegrationService
{
    public function syncOrder(Order $order): void
    {
        try {
            // POS APIを呼び出し
            $response = $this->callPosApi('POST', '/orders', $order->toArray());
            
            if ($response->successful()) {
                $order->update([
                    'pos_sync_status' => 'synced',
                    'pos_synced_at' => now(),
                ]);
            } else {
                throw new BusinessException('EXT-POS-001');
            }
        } catch (\Exception $e) {
            // エラーをログに記録
            Log::error('POS sync failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            // 変更ログに「受付中」として記録
            ChangeLog::create([
                'entity_type' => 'orders',
                'entity_id' => $order->id,
                'action' => 'created',
                'changes' => $order->toArray(),
                'is_synced' => false,
                'user_id' => auth()->id(),
            ]);
            
            // 注文は正常に処理（POSへの同期は後で再試行）
            $order->update([
                'pos_sync_status' => 'pending',
            ]);
        }
    }
}
```

### 6.2 POS同期リトライ処理
```php
// app/Jobs/RetrySyncPosOrder.php
class RetrySyncPosOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1分、5分、15分
    
    public function __construct(
        private Order $order
    ) {}
    
    public function handle(PosIntegrationService $posService): void
    {
        // 既に同期済みの場合はスキップ
        if ($this->order->pos_sync_status === 'synced') {
            return;
        }
        
        try {
            $posService->syncOrder($this->order);
        } catch (BusinessException $e) {
            if ($e->getErrorCode() === 'EXT-POS-001') {
                // 最大試行回数に達した場合
                if ($this->attempts() >= $this->tries) {
                    Log::error('POS sync permanently failed', [
                        'order_id' => $this->order->id,
                    ]);
                    
                    // 手動対応が必要なフラグを立てる
                    $this->order->update([
                        'pos_sync_status' => 'failed',
                        'requires_manual_sync' => true,
                    ]);
                    
                    return;
                }
                
                // リトライ
                throw $e;
            }
        }
    }
}
```

## 7. エラーログ管理

### 7.1 ログ設定
```php
// config/logging.php
'channels' => [
    'error' => [
        'driver' => 'daily',
        'path' => storage_path('logs/error.log'),
        'level' => 'error',
        'days' => 365, // 1年間保持
        'permission' => 0664,
    ],
    
    'business' => [
        'driver' => 'daily',
        'path' => storage_path('logs/business.log'),
        'level' => 'info',
        'days' => 365, // 1年間保持
    ],
    
    'pos_integration' => [
        'driver' => 'daily',
        'path' => storage_path('logs/pos_integration.log'),
        'level' => 'debug',
        'days' => 365, // 1年間保持
    ],
],
```

### 7.2 エラーログ記録
```php
// app/Services/ErrorLoggingService.php
class ErrorLoggingService
{
    public function logError(
        Throwable $exception,
        Request $request,
        ?User $user = null
    ): void {
        $context = [
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception instanceof BusinessException 
                ? $exception->getErrorCode() 
                : 'UNKNOWN',
            'request' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            'user' => $user ? [
                'id' => $user->id,
                'role' => $user->role,
            ] : null,
            'trace' => $exception->getTraceAsString(),
        ];
        
        // エラータイプに応じて適切なチャンネルに記録
        if ($exception instanceof BusinessException) {
            Log::channel('business')->error('Business error occurred', $context);
        } elseif (str_contains($exception->getMessage(), 'POS')) {
            Log::channel('pos_integration')->error('POS integration error', $context);
        } else {
            Log::channel('error')->error('System error occurred', $context);
        }
    }
}
```

## 8. フロントエンドエラー表示

### 8.1 Livewireコンポーネント
```blade
{{-- resources/views/livewire/shared/error-alert.blade.php --}}
@if (session()->has('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative mb-4"
         x-data="{ show: true }"
         x-show="show"
         x-transition>
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">
                    {{ session('error') }}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <button @click="show = false" class="text-red-400 hover:text-red-600">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
@endif
```

### 8.2 Mary UIエラー表示
```blade
{{-- エラーメッセージ表示 --}}
<x-mary-alert type="error" dismissible wire:key="error-{{ now() }}">
    {{ $errorMessage }}
</x-mary-alert>

{{-- バリデーションエラー表示 --}}
@error('quantity')
    <x-mary-error>{{ $message }}</x-mary-error>
@enderror
```

## 9. テスト設計

### 9.1 エラーハンドリングテスト
```php
class ErrorHandlingTest extends TestCase
{
    /** @test */
    public function it_shows_user_friendly_error_for_out_of_stock()
    {
        $menuItem = Product::factory()->create(['is_available' => false]);
        
        $response = $this->postJson('/api/v1/cart/add', [
            'product_id' => $menuItem->id,
            'quantity' => 1,
        ]);
        
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'BIZ-STK-001',
                    'message' => '申し訳ございません。この商品は現在売り切れです。',
                ],
            ]);
    }
    
    /** @test */
    public function it_returns_error_in_requested_language()
    {
        $menuItem = Product::factory()->create(['is_available' => false]);
        
        $response = $this->postJson('/api/v1/cart/add', 
            ['product_id' => $menuItem->id, 'quantity' => 1],
            ['Accept-Language' => 'en']
        );
        
        $response->assertJson([
            'error' => [
                'message' => 'Sorry, this item is currently out of stock.',
            ],
        ]);
    }
    
    /** @test */
    public function it_logs_errors_for_one_year()
    {
        // ログ保持期間のテスト
        $this->assertEquals(365, config('logging.channels.error.days'));
        $this->assertEquals(365, config('logging.channels.business.days'));
        $this->assertEquals(365, config('logging.channels.pos_integration.days'));
    }
}
```

---

このエラーハンドリング設計により、ユーザーフレンドリーで多言語対応したエラー処理を実現し、適切なログ管理とPOS連携エラーの対応を行います。