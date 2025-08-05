# REST API連携パターン文書

## 1. REST API設計概要

### 1.1 基本方針
モバイルオーダーシステムにおけるREST API設計は、RESTful原則に従い、予測可能で一貫性のあるインターフェースを提供します。Laravel 11の機能を最大限活用し、効率的なAPI開発を実現します。

### 1.2 技術スタック
- **Laravel 11**: APIフレームワーク
- **Laravel Sanctum**: API認証
- **Spatie Laravel Query Builder**: 高度なクエリ処理
- **Laravel API Resources**: レスポンス変換
- **Guzzle HTTP**: 外部API連携
- **OpenAPI (Swagger)**: API仕様書

### 1.3 設計原則
- **RESTful設計**: HTTP動詞とリソースベースのURL
- **統一性**: 一貫したレスポンス形式とエラーハンドリング
- **セキュリティ**: 認証・認可・入力検証の徹底
- **パフォーマンス**: 効率的なクエリとキャッシュ戦略
- **拡張性**: バージョニングと後方互換性

## 2. API構造設計

### 2.1 URL設計パターン

#### リソースベースURL構造
```
/api/v1/menu-items              # メニューアイテム一覧・作成
/api/v1/menu-items/{id}         # 特定メニューアイテム操作
/api/v1/menu-items/{id}/reviews # メニューアイテムのレビュー
/api/v1/categories              # カテゴリー操作
/api/v1/orders                  # 注文操作
/api/v1/orders/{id}/items       # 注文アイテム
/api/v1/auth/login              # 認証関連
/api/v1/auth/register           # ユーザー登録
/api/v1/admin/dashboard         # 管理者専用
```

#### HTTP動詞の使い分け
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // メニューアイテム
    Route::get('menu-items', [MenuItemController::class, 'index']);         // 一覧取得
    Route::post('menu-items', [MenuItemController::class, 'store']);        // 作成
    Route::get('menu-items/{id}', [MenuItemController::class, 'show']);     // 詳細取得
    Route::put('menu-items/{id}', [MenuItemController::class, 'update']);   // 更新
    Route::patch('menu-items/{id}', [MenuItemController::class, 'update']); // 部分更新
    Route::delete('menu-items/{id}', [MenuItemController::class, 'destroy']); // 削除
    
    // ネストしたリソース
    Route::get('menu-items/{id}/reviews', [ReviewController::class, 'index']);
    Route::post('menu-items/{id}/reviews', [ReviewController::class, 'store']);
    
    // カスタムアクション
    Route::post('menu-items/{id}/toggle-availability', [MenuItemController::class, 'toggleAvailability']);
    Route::get('menu-items/popular', [MenuItemController::class, 'popular']);
});
```

### 2.2 レスポンス形式の統一

#### 標準レスポンス構造
```php
// app/Http/Responses/ApiResponse.php
<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $status);
    }
    
    public static function error(string $message, $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ], $status);
    }
    
    public static function paginated($data, string $message = ''): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

#### APIリソースクラス
```php
// app/Http/Resources/MenuItemResource.php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image_url' => $this->image_url,
            'is_available' => $this->is_available,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'nutrition_info' => $this->when($this->nutrition_info, $this->nutrition_info),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

// app/Http/Resources/MenuItemCollection.php
class MenuItemCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection,
            'summary' => [
                'total_items' => $this->collection->count(),
                'available_items' => $this->collection->where('is_available', true)->count(),
                'categories_count' => $this->collection->pluck('category_id')->unique()->count(),
            ],
        ];
    }
}
```

### 2.3 エラーハンドリング

#### 統一されたエラーレスポンス
```php
// app/Exceptions/Handler.php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Responses\ApiResponse;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }
        
        return parent::render($request, $exception);
    }
    
    private function handleApiException($request, Throwable $exception)
    {
        // バリデーションエラー
        if ($exception instanceof ValidationException) {
            return ApiResponse::error(
                'バリデーションエラーが発生しました',
                $exception->errors(),
                422
            );
        }
        
        // モデルが見つからない
        if ($exception instanceof ModelNotFoundException) {
            return ApiResponse::error(
                'リソースが見つかりません',
                null,
                404
            );
        }
        
        // HTTPエラー
        if ($exception instanceof NotFoundHttpException) {
            return ApiResponse::error(
                'エンドポイントが見つかりません',
                null,
                404
            );
        }
        
        // ビジネス例外
        if ($exception instanceof BusinessException) {
            return ApiResponse::error(
                $exception->getMessage(),
                ['code' => $exception->getCode()],
                400
            );
        }
        
        // その他のエラー
        if (config('app.debug')) {
            return ApiResponse::error(
                $exception->getMessage(),
                [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ],
                500
            );
        }
        
        return ApiResponse::error(
            'サーバーエラーが発生しました',
            null,
            500
        );
    }
}
```

#### カスタム例外クラス
```php
// app/Exceptions/BusinessException.php
<?php

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    private array $context;
    
    public function __construct(string $code, string $message = '', array $context = [])
    {
        $this->code = $code;
        $this->context = $context;
        
        // エラーコードに対応するメッセージを取得
        $translatedMessage = trans("errors.business.{$code}", $context);
        
        parent::__construct($translatedMessage ?: $message);
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
}
```

## 3. 認証・認可パターン

### 3.1 Laravel Sanctum認証

#### Sanctum設定
```php
// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),
    
    'guard' => ['web'],
    
    'expiration' => env('SANCTUM_EXPIRATION', 60 * 24 * 7), // 7日間
    
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

#### 認証API実装
```php
// app/Http/Controllers/Api/AuthController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        
        $token = $user->createToken('mobile-order-token', [
            'menu:read',
            'order:create',
            'order:read',
        ])->plainTextToken;
        
        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
            'expires_at' => now()->addDays(7)->toISOString(),
        ], 'ユーザー登録が完了しました');
    }
    
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error(
                'メールアドレスまたはパスワードが正しくありません',
                null,
                401
            );
        }
        
        // 既存のトークンを削除
        $user->tokens()->delete();
        
        $abilities = $this->getUserAbilities($user);
        $token = $user->createToken('mobile-order-token', $abilities)->plainTextToken;
        
        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
            'abilities' => $abilities,
            'expires_at' => now()->addDays(7)->toISOString(),
        ], 'ログインしました');
    }
    
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return ApiResponse::success(null, 'ログアウトしました');
    }
    
    public function user(Request $request)
    {
        return ApiResponse::success(
            new UserResource($request->user())
        );
    }
    
    private function getUserAbilities(User $user): array
    {
        $abilities = ['menu:read', 'order:create', 'order:read'];
        
        if ($user->role === 'admin') {
            $abilities[] = 'admin:*';
        }
        
        if ($user->role === 'staff') {
            $abilities[] = 'order:update';
            $abilities[] = 'menu:create';
            $abilities[] = 'menu:update';
        }
        
        return $abilities;
    }
}
```

### 3.2 認可ミドルウェア

#### カスタム認可ミドルウェア
```php
// app/Http/Middleware/CheckApiAbility.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;

class CheckApiAbility
{
    public function handle(Request $request, Closure $next, string $ability)
    {
        $user = $request->user();
        
        if (!$user) {
            return ApiResponse::error('認証が必要です', null, 401);
        }
        
        if (!$user->tokenCan($ability)) {
            return ApiResponse::error(
                'このアクションを実行する権限がありません',
                ['required_ability' => $ability],
                403
            );
        }
        
        return $next($request);
    }
}
```

#### ルートでの認可適用
```php
// routes/api.php
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // 基本的な読み込み権限
    Route::middleware(['ability:menu:read'])->group(function () {
        Route::get('menu-items', [MenuItemController::class, 'index']);
        Route::get('menu-items/{id}', [MenuItemController::class, 'show']);
        Route::get('categories', [CategoryController::class, 'index']);
    });
    
    // 注文作成権限
    Route::middleware(['ability:order:create'])->group(function () {
        Route::post('orders', [OrderController::class, 'store']);
    });
    
    // 注文読み込み権限
    Route::middleware(['ability:order:read'])->group(function () {
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
    });
    
    // 管理者権限
    Route::middleware(['ability:admin:*'])->prefix('admin')->group(function () {
        Route::apiResource('menu-items', AdminMenuItemController::class);
        Route::apiResource('categories', AdminCategoryController::class);
        Route::get('dashboard', [AdminDashboardController::class, 'index']);
    });
});
```

## 4. データ取得・操作パターン

### 4.1 高度なクエリ処理

#### Spatie Query Builder活用
```php
// app/Http/Controllers/Api/MenuItemController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuItemCollection;
use App\Http\Responses\ApiResponse;
use App\Models\MenuItem;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class MenuItemController extends Controller
{
    public function index()
    {
        $menuItems = QueryBuilder::for(MenuItem::class)
            ->allowedFilters([
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('is_available'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('description'),
                AllowedFilter::scope('price_range'),
            ])
            ->allowedSorts([
                'name',
                'price',
                'created_at',
                AllowedSort::field('popularity', 'order_count'),
            ])
            ->allowedIncludes(['category', 'reviews'])
            ->with(['category'])
            ->where('is_active', true)
            ->paginate(request('per_page', 20));
        
        return ApiResponse::paginated(
            new MenuItemCollection($menuItems),
            'メニューアイテムを取得しました'
        );
    }
    
    public function show(MenuItem $menuItem)
    {
        $menuItem = QueryBuilder::for(MenuItem::where('id', $menuItem->id))
            ->allowedIncludes(['category', 'reviews.user', 'nutrition_info'])
            ->firstOrFail();
        
        return ApiResponse::success(
            new MenuItemResource($menuItem),
            'メニューアイテムの詳細を取得しました'
        );
    }
}
```

#### カスタムスコープ
```php
// app/Models/MenuItem.php
class MenuItem extends Model
{
    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        
        return $query;
    }
    
    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('orders')
            ->orderByDesc('orders_count')
            ->limit($limit);
    }
    
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->where('is_active', true);
    }
}
```

### 4.2 バッチ処理とトランザクション

#### 注文処理API
```php
// app/Http/Controllers/Api/OrderController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\MenuItem;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }
    
    public function store(CreateOrderRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // 在庫確認
            $items = MenuItem::whereIn('id', collect($request->items)->pluck('id'))
                ->available()
                ->get();
            
            if ($items->count() !== count($request->items)) {
                throw new BusinessException('ORD-ITM-001', '選択された商品の一部が利用できません');
            }
            
            // 注文作成
            $order = $this->orderService->createOrder(
                $request->user(),
                $request->items,
                $request->payment_method,
                $request->notes
            );
            
            DB::commit();
            
            return ApiResponse::success(
                new OrderResource($order->load(['items.menuItem', 'user'])),
                '注文が作成されました',
                201
            );
            
        } catch (BusinessException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('注文の作成中にエラーが発生しました', null, 500);
        }
    }
    
    public function index()
    {
        $orders = QueryBuilder::for(Order::class)
            ->where('user_id', request()->user()->id)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::scope('date_range'),
            ])
            ->allowedSorts(['created_at', 'total'])
            ->with(['items.menuItem'])
            ->paginate(request('per_page', 10));
        
        return ApiResponse::paginated(
            OrderResource::collection($orders),
            '注文履歴を取得しました'
        );
    }
}
```

#### 注文サービスクラス
```php
// app/Services/OrderService.php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Exceptions\BusinessException;

class OrderService
{
    public function createOrder(User $user, array $items, string $paymentMethod, ?string $notes = null): Order
    {
        // 合計金額計算
        $total = collect($items)->sum(function ($item) {
            $menuItem = MenuItem::find($item['id']);
            return $menuItem->price * $item['quantity'];
        });
        
        // 注文作成
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => $total,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'order_number' => $this->generateOrderNumber(),
        ]);
        
        // 注文アイテム作成
        foreach ($items as $item) {
            $menuItem = MenuItem::find($item['id']);
            
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'quantity' => $item['quantity'],
                'price' => $menuItem->price,
                'subtotal' => $menuItem->price * $item['quantity'],
            ]);
        }
        
        // 注文確認イベント発火
        event(new OrderCreated($order));
        
        return $order;
    }
    
    private function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('Ymd') . '-' . str_pad(
            Order::whereDate('created_at', today())->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}
```

## 5. キャッシュとパフォーマンス最適化

### 5.1 レスポンスキャッシュ

#### HTTP キャッシュヘッダー
```php
// app/Http/Controllers/Api/MenuItemController.php
public function index()
{
    $cacheKey = 'menu_items_' . md5(request()->getQueryString());
    
    $menuItems = Cache::remember($cacheKey, 300, function () {
        return QueryBuilder::for(MenuItem::class)
            ->allowedFilters([...])
            ->allowedSorts([...])
            ->with(['category'])
            ->where('is_active', true)
            ->paginate(request('per_page', 20));
    });
    
    return ApiResponse::paginated(
        new MenuItemCollection($menuItems),
        'メニューアイテムを取得しました'
    )->header('Cache-Control', 'public, max-age=300')
     ->header('ETag', md5($menuItems->toJson()));
}

public function show(MenuItem $menuItem)
{
    $etag = md5($menuItem->updated_at . $menuItem->id);
    
    if (request()->header('If-None-Match') === $etag) {
        return response('', 304);
    }
    
    return ApiResponse::success(
        new MenuItemResource($menuItem->load(['category', 'nutrition_info'])),
        'メニューアイテムの詳細を取得しました'
    )->header('ETag', $etag)
     ->header('Cache-Control', 'public, max-age=600');
}
```

### 5.2 データベースクエリ最適化

#### N+1問題の回避
```php
// 悪い例
public function index()
{
    $orders = Order::all(); // 1回のクエリ
    
    foreach ($orders as $order) {
        echo $order->user->name; // N回のクエリ（N+1問題）
        echo $order->items->count(); // さらにN回のクエリ
    }
}

// 良い例
public function index()
{
    $orders = Order::with(['user', 'items.menuItem']) // 必要なリレーションを事前読み込み
        ->get();
    
    foreach ($orders as $order) {
        echo $order->user->name; // 追加クエリなし
        echo $order->items->count(); // 追加クエリなし
    }
}
```

#### インデックス最適化
```php
// database/migrations/add_indexes_to_menu_items_table.php
public function up()
{
    Schema::table('menu_items', function (Blueprint $table) {
        $table->index(['is_available', 'is_active']); // 複合インデックス
        $table->index(['category_id', 'created_at']); // フィルタリング用
        $table->index('price'); // ソート用
        $table->fullText(['name', 'description']); // 全文検索用
    });
}
```

## 6. レート制限とセキュリティ

### 6.1 レート制限設定

#### カスタムレート制限
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

protected $routeMiddleware = [
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
];
```

```php
// routes/api.php
Route::middleware(['throttle:60,1'])->prefix('v1')->group(function () {
    // 一般API（1分間に60リクエスト）
    Route::get('menu-items', [MenuItemController::class, 'index']);
});

Route::middleware(['throttle:10,1'])->prefix('v1')->group(function () {
    // 認証API（1分間に10リクエスト）
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);
});

Route::middleware(['auth:sanctum', 'throttle:100,1'])->prefix('v1')->group(function () {
    // 認証済みユーザー（1分間に100リクエスト）
    Route::post('orders', [OrderController::class, 'store']);
});
```

### 6.2 入力検証とセキュリティ

#### フォームリクエスト
```php
// app/Http/Requests/CreateOrderRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }
    
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1|max:20',
            'items.*.id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:10',
            'payment_method' => ['required', Rule::in(['cash', 'card', 'qr_code'])],
            'notes' => 'nullable|string|max:500',
            'delivery_address' => 'nullable|string|max:255',
        ];
    }
    
    public function messages(): array
    {
        return [
            'items.required' => '注文アイテムは必須です',
            'items.min' => '最低1つのアイテムを選択してください',
            'items.max' => '一度に注文できるアイテムは20個までです',
            'items.*.id.exists' => '選択されたメニューアイテムが存在しません',
            'items.*.quantity.max' => '1つのアイテムにつき最大10個まで注文できます',
            'payment_method.in' => '無効な支払い方法です',
        ];
    }
    
    protected function prepareForValidation()
    {
        // 入力データのサニタイズ
        $this->merge([
            'notes' => strip_tags($this->notes),
        ]);
    }
}
```

#### SQLインジェクション対策
```php
// 悪い例（SQLインジェクション脆弱性あり）
public function search(Request $request)
{
    $query = $request->input('query');
    $results = DB::select("SELECT * FROM menu_items WHERE name LIKE '%{$query}%'");
    return $results;
}

// 良い例（パラメータバインディング使用）
public function search(Request $request)
{
    $query = $request->input('query');
    $results = MenuItem::where('name', 'LIKE', "%{$query}%")
        ->orWhere('description', 'LIKE', "%{$query}%")
        ->get();
    return $results;
}
```

## 7. 外部API連携パターン

### 7.1 決済API連携

#### 決済サービス抽象化
```php
// app/Contracts/PaymentServiceInterface.php
<?php

namespace App\Contracts;

interface PaymentServiceInterface
{
    public function processPayment(int $amount, string $method, array $options = []): array;
    public function refundPayment(string $transactionId, int $amount): array;
    public function getPaymentStatus(string $transactionId): array;
}

// app/Services/StripePaymentService.php
class StripePaymentService implements PaymentServiceInterface
{
    public function __construct(private string $secretKey)
    {
        Stripe::setApiKey($this->secretKey);
    }
    
    public function processPayment(int $amount, string $method, array $options = []): array
    {
        try {
            $intent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'jpy',
                'payment_method' => $method,
                'confirm' => true,
                'metadata' => $options['metadata'] ?? [],
            ]);
            
            return [
                'success' => true,
                'transaction_id' => $intent->id,
                'status' => $intent->status,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
```

#### HTTP クライアント設定
```php
// app/Services/ExternalApiService.php
<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

abstract class ExternalApiService
{
    protected string $baseUrl;
    protected array $defaultHeaders;
    protected int $timeout;
    
    public function __construct()
    {
        $this->timeout = config('services.external_api.timeout', 30);
        $this->defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'MobileOrderSystem/1.0',
        ];
    }
    
    protected function httpClient()
    {
        return Http::timeout($this->timeout)
            ->withHeaders($this->defaultHeaders)
            ->baseUrl($this->baseUrl)
            ->retry(3, 1000) // 3回リトライ、1秒間隔
            ->withOptions([
                'verify' => config('app.env') === 'production',
            ]);
    }
    
    protected function handleResponse($response)
    {
        if ($response->successful()) {
            return $response->json();
        }
        
        // ログ記録
        logger()->error('External API Error', [
            'status' => $response->status(),
            'body' => $response->body(),
            'url' => $response->effectiveUri(),
        ]);
        
        throw new ExternalServiceException(
            'External service error: ' . $response->status()
        );
    }
}
```

### 7.2 配信サービス連携

#### プッシュ通知サービス
```php
// app/Services/PushNotificationService.php
<?php

namespace App\Services;

class PushNotificationService extends ExternalApiService
{
    protected string $baseUrl = 'https://fcm.googleapis.com/fcm';
    
    public function __construct()
    {
        parent::__construct();
        $this->defaultHeaders['Authorization'] = 'key=' . config('services.fcm.server_key');
    }
    
    public function sendOrderNotification(string $deviceToken, array $orderData): bool
    {
        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => '注文状況更新',
                'body' => "ご注文（{$orderData['order_number']}）のステータスが更新されました",
                'icon' => 'order_icon',
                'sound' => 'default',
            ],
            'data' => [
                'order_id' => $orderData['id'],
                'status' => $orderData['status'],
                'type' => 'order_update',
            ],
        ];
        
        try {
            $response = $this->httpClient()->post('/send', $payload);
            return $this->handleResponse($response)['success'] ?? false;
        } catch (Exception $e) {
            logger()->error('Push notification failed', [
                'device_token' => $deviceToken,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
```

## 8. API仕様書とドキュメント

### 8.1 OpenAPI仕様書

#### Swagger設定
```yaml
# storage/api-docs/api-docs.yaml
openapi: 3.0.0
info:
  title: Mobile Order System API
  description: モバイルオーダーシステムのREST API
  version: 1.0.0
  contact:
    name: API Support
    email: support@mobile-order.com

servers:
  - url: https://api.mobile-order.com/v1
    description: Production server
  - url: https://staging-api.mobile-order.com/v1
    description: Staging server

components:
  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    MenuItem:
      type: object
      properties:
        id:
          type: integer
          example: 1
        name:
          type: string
          example: "チーズバーガー"
        description:
          type: string
          example: "美味しいチーズバーガー"
        price:
          type: number
          format: float
          example: 580.0
        image_url:
          type: string
          format: uri
          example: "https://example.com/images/burger.jpg"
        is_available:
          type: boolean
          example: true
        category:
          $ref: '#/components/schemas/Category'

    ApiResponse:
      type: object
      properties:
        success:
          type: boolean
        message:
          type: string
        data:
          type: object
        timestamp:
          type: string
          format: date-time

paths:
  /menu-items:
    get:
      summary: メニューアイテム一覧取得
      parameters:
        - name: category_id
          in: query
          schema:
            type: integer
        - name: is_available
          in: query
          schema:
            type: boolean
        - name: sort
          in: query
          schema:
            type: string
            enum: [name, price, created_at, -name, -price, -created_at]
      responses:
        200:
          description: 成功
          content:
            application/json:
              schema:
                allOf:
                  - $ref: '#/components/schemas/ApiResponse'
                  - properties:
                      data:
                        type: array
                        items:
                          $ref: '#/components/schemas/MenuItem'
```

### 8.2 自動テスト統合

#### API仕様書テスト
```php
// tests/Feature/Api/ApiDocumentationTest.php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiDocumentationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_menu_items_endpoint_matches_documentation()
    {
        // Arrange
        MenuItem::factory()->count(3)->create();
        
        // Act
        $response = $this->getJson('/api/v1/menu-items');
        
        // Assert - レスポンス構造が仕様書と一致するか確認
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'image_url',
                        'is_available',
                        'category',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
                'timestamp',
            ]);
    }
}
```

## 9. テストとモニタリング

### 9.1 APIテスト戦略

#### 統合テスト
```php
// tests/Feature/Api/OrderApiTest.php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\MenuItem;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creates_order_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        $items = MenuItem::factory()->count(2)->create(['is_available' => true]);
        
        Sanctum::actingAs($user, ['order:create']);
        
        $orderData = [
            'items' => [
                ['id' => $items[0]->id, 'quantity' => 2],
                ['id' => $items[1]->id, 'quantity' => 1],
            ],
            'payment_method' => 'card',
            'notes' => 'テスト注文',
        ];
        
        // Act
        $response = $this->postJson('/api/v1/orders', $orderData);
        
        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total',
                    'items' => [
                        '*' => [
                            'menu_item',
                            'quantity',
                            'price',
                            'subtotal',
                        ]
                    ],
                ],
            ]);
        
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_method' => 'card',
        ]);
    }
    
    public function test_requires_authentication_for_order_creation()
    {
        $response = $this->postJson('/api/v1/orders', []);
        
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => '認証が必要です',
            ]);
    }
}
```

### 9.2 パフォーマンスモニタリング

#### API レスポンス時間計測
```php
// app/Http/Middleware/ApiPerformanceMiddleware.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiPerformanceMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // ミリ秒
        
        // 遅いAPIリクエストをログ出力
        if ($duration > 1000) { // 1秒以上
            Log::warning('Slow API Request', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'user_id' => $request->user()?->id,
            ]);
        }
        
        // レスポンスヘッダーに処理時間を追加
        $response->headers->set('X-Response-Time', $duration . 'ms');
        
        return $response;
    }
}
```

---

このREST API連携パターン文書により、効率的で保守性の高いAPI設計・実装・運用を実現できます。認証・認可、データ処理、外部サービス連携、パフォーマンス最適化、セキュリティ対策を統合的に管理し、高品質なモバイルオーダーシステムのAPIを構築できます。