# テスト設計書

## 1. テスト戦略

### 1.1 テスト方針
- **品質第一**: 機能追加・修正時には必ずテストを実装
- **自動化**: 手動テストを最小限に抑え、自動テストを重視
- **継続的テスト**: CI/CDパイプラインでの自動実行
- **テストピラミッド**: 単体テスト > 統合テスト > E2Eテストの構成

### 1.2 テストレベル
1. **単体テスト（Unit Test）**: 個別のクラス・メソッドのテスト
2. **機能テスト（Feature Test）**: API エンドポイントのテスト
3. **統合テスト（Integration Test）**: 複数コンポーネント間の連携テスト
4. **E2Eテスト（End-to-End Test）**: ユーザーシナリオ全体のテスト

### 1.3 テスト環境
- **Testing Environment**: Laravel の標準テスト環境
- **テストDB**: SQLite in-memory database
- **テストデータ**: Factory + Seeder
- **モック**: 外部API（POS、決済システム等）

## 2. テストツール構成

### 2.1 テストフレームワーク
```php
// composer.json
{
    "require-dev": {
        "phpunit/phpunit": "^10.1",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0",
        "mockery/mockery": "^1.4.4"
    }
}
```

### 2.2 テスト支援ライブラリ
- **Laravel Testing**: HTTP テスト、DB テスト
- **Pest**: 記述的テスト（PHPUnitの代替）
- **Mockery**: モックオブジェクト作成
- **Laravel Dusk**: ブラウザテスト（E2E用）

### 2.3 テスト設定
```php
// phpunit.xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

## 3. 単体テスト（Unit Test）

### 3.1 対象範囲
- **Model**: Eloquentモデルのメソッド
- **Service**: ビジネスロジックを含むサービスクラス
- **Repository**: データアクセス層
- **Utility**: ヘルパークラス・関数

### 3.2 単体テスト実装例
```php
// tests/Unit/Models/ProductTest.php
use App\Models\Product;
use App\Models\MenuCategory;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_belongs_to_a_category()
    {
        $category = MenuCategory::factory()->create();
        $menuItem = Product::factory()->create(['category_id' => $category->id]);
        
        $this->assertInstanceOf(MenuCategory::class, $menuItem->category);
        $this->assertEquals($category->id, $menuItem->category->id);
    }
    
    /** @test */
    public function it_calculates_total_price_with_options()
    {
        $menuItem = Product::factory()->create(['price' => 1000]);
        $options = [
            ['price_modifier' => 200],
            ['price_modifier' => 100],
        ];
        
        $totalPrice = $menuItem->calculateTotalPrice($options, 2);
        
        $this->assertEquals(2600, $totalPrice); // (1000 + 200 + 100) * 2
    }
    
    /** @test */
    public function it_returns_localized_name()
    {
        $menuItem = Product::factory()->create([
            'name' => 'ハンバーガー',
            'translations' => [
                'en' => 'Hamburger',
                'zh-TW' => '漢堡',
            ]
        ]);
        
        $this->assertEquals('Hamburger', $menuItem->getLocalizedName('en'));
        $this->assertEquals('漢堡', $menuItem->getLocalizedName('zh-TW'));
        $this->assertEquals('ハンバーガー', $menuItem->getLocalizedName('ja'));
    }
}
```

### 3.3 サービスクラステスト
```php
// tests/Unit/Services/CartServiceTest.php
use App\Services\CartService;
use App\Models\Product;
use App\Exceptions\BusinessException;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private CartService $cartService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
    }
    
    /** @test */
    public function it_adds_item_to_cart()
    {
        $menuItem = Product::factory()->create(['is_available' => true]);
        
        $this->cartService->addToCart($menuItem->id, 2);
        
        $cartItems = $this->cartService->getCartItems();
        $this->assertCount(1, $cartItems);
        $this->assertEquals($menuItem->id, $cartItems[0]['product_id']);
        $this->assertEquals(2, $cartItems[0]['quantity']);
    }
    
    /** @test */
    public function it_throws_exception_when_adding_unavailable_item()
    {
        $menuItem = Product::factory()->create(['is_available' => false]);
        
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('BIZ-STK-001');
        
        $this->cartService->addToCart($menuItem->id, 1);
    }
    
    /** @test */
    public function it_validates_cart_and_removes_unavailable_items()
    {
        $availableItem = Product::factory()->create(['is_available' => true]);
        $unavailableItem = Product::factory()->create(['is_available' => false]);
        
        // カートに両方の商品を追加（強制的に）
        $this->cartService->forceAddToCart($availableItem->id, 1);
        $this->cartService->forceAddToCart($unavailableItem->id, 1);
        
        $errors = $this->cartService->validateCart();
        
        $this->assertCount(1, $errors);
        $this->assertEquals('out_of_stock', $errors[0]['reason']);
        
        // 利用可能な商品のみがカートに残っている
        $cartItems = $this->cartService->getCartItems();
        $this->assertCount(1, $cartItems);
        $this->assertEquals($availableItem->id, $cartItems[0]['product_id']);
    }
}
```

## 4. 機能テスト（Feature Test）

### 4.1 対象範囲
- **API エンドポイント**: 全APIの正常系・異常系
- **Web Controller**: 管理画面の機能
- **認証・認可**: ログイン、権限チェック
- **ミドルウェア**: レート制限、CORS等

### 4.2 API機能テスト
```php
// tests/Feature/Api/MenuApiTest.php
class MenuApiTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_returns_products_list()
    {
        $category = MenuCategory::factory()->create();
        $menuItems = Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        
        $response = $this->getJson('/api/v1/menu-items');
        
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id', 'name', 'price', 'image_url', 'is_available'
                    ]
                ],
                'meta' => [
                    'current_page', 'per_page', 'total', 'last_page'
                ]
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }
    
    /** @test */
    public function it_filters_products_by_category()
    {
        $category1 = MenuCategory::factory()->create();
        $category2 = MenuCategory::factory()->create();
        
        Product::factory()->count(2)->create(['category_id' => $category1->id]);
        Product::factory()->count(3)->create(['category_id' => $category2->id]);
        
        $response = $this->getJson("/api/v1/menu-items?category_id={$category1->id}");
        
        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
    
    /** @test */
    public function it_requires_authentication_for_order_creation()
    {
        $response = $this->postJson('/api/v1/orders', [
            'items' => []
        ]);
        
        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
```

### 4.3 認証テスト
```php
// tests/Feature/Auth/SessionAuthTest.php
class SessionAuthTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_creates_session_with_valid_qr_code()
    {
        $session = Session::factory()->active()->create();
        
        $response = $this->postJson('/api/v1/auth/session', [
            'qr_code' => $session->qr_code,
            'customer_count' => 2,
        ]);
        
        $response->assertOk()
            ->assertJsonStructure([
                'token', 'session_id', 'expires_at', 'table_number'
            ]);
        
        // セッションが開始状態に更新されている
        $session->refresh();
        $this->assertNotNull($session->started_at);
        $this->assertEquals(2, $session->customer_count);
    }
    
    /** @test */
    public function it_rejects_expired_qr_code()
    {
        $session = Session::factory()->expired()->create();
        
        $response = $this->postJson('/api/v1/auth/session', [
            'qr_code' => $session->qr_code,
            'customer_count' => 1,
        ]);
        
        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'AUTH-SES-001');
    }
    
    /** @test */
    public function authenticated_user_can_create_order()
    {
        $user = User::factory()->Product::factory()->create();
        $session = Session::factory()->active()->create();
        $menuItem = Product::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'session_id' => $session->id,
                'items' => [
                    [
                        'product_id' => $menuItem->id,
                        'quantity' => 2,
                        'options' => [],
                    ]
                ]
            ]);
        
        $response->assertCreated()
            ->assertJsonPath('success', true);
        
        $this->assertDatabaseHas('orders', [
            'session_id' => $session->id,
            'guest_token' => $user->id,
            'status' => 'pending',
        ]);
    }
}
```

## 5. 統合テスト（Integration Test）

### 5.1 対象範囲
- **POS連携**: 注文同期、メニュー同期
- **決済システム**: 決済処理フロー
- **メール送信**: 通知システム
- **ファイルアップロード**: 画像アップロード

### 5.2 POS連携テスト
```php
// tests/Integration/PosIntegrationTest.php
use App\Services\PosIntegrationService;
use Illuminate\Http\Client\Fake;
use Illuminate\Support\Facades\Http;

class PosIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_syncs_order_to_pos_successfully()
    {
        Http::fake([
            'pos-api.example.com/orders' => Http::response([
                'success' => true,
                'pos_order_id' => 'POS123'
            ], 200)
        ]);
        
        $order = Order::factory()->create();
        $posService = new PosIntegrationService();
        
        $posService->syncOrder($order);
        
        $order->refresh();
        $this->assertEquals('synced', $order->pos_sync_status);
        $this->assertNotNull($order->pos_synced_at);
        
        Http::assertSent(function ($request) use ($order) {
            return $request->url() === 'https://pos-api.example.com/orders' &&
                   $request['order_id'] === $order->id;
        });
    }
    
    /** @test */
    public function it_handles_pos_api_failure()
    {
        Http::fake([
            'pos-api.example.com/orders' => Http::response([], 500)
        ]);
        
        $order = Order::factory()->create();
        $posService = new PosIntegrationService();
        
        $posService->syncOrder($order);
        
        $order->refresh();
        $this->assertEquals('pending', $order->pos_sync_status);
        
        // 変更ログに記録されている
        $this->assertDatabaseHas('change_logs', [
            'entity_type' => 'orders',
            'entity_id' => $order->id,
            'is_synced' => false,
        ]);
    }
    
    /** @test */
    public function it_retries_failed_pos_sync()
    {
        $order = Order::factory()->create(['pos_sync_status' => 'pending']);
        
        // 最初は失敗、2回目は成功
        Http::fake([
            'pos-api.example.com/orders' => Http::sequence()
                ->push([], 500)
                ->push(['success' => true, 'pos_order_id' => 'POS456'], 200)
        ]);
        
        // リトライジョブを実行
        $job = new RetrySyncPosOrder($order);
        
        // 1回目：失敗
        try {
            $job->handle(new PosIntegrationService());
        } catch (Exception $e) {
            // エラーが投げられることを確認
        }
        
        // 2回目：成功
        $job->handle(new PosIntegrationService());
        
        $order->refresh();
        $this->assertEquals('synced', $order->pos_sync_status);
    }
}
```

## 6. E2Eテスト（End-to-End Test）

### 6.1 Laravel Dusk設定
```php
// tests/DuskTestCase.php
abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;
    
    public static function prepare()
    {
        if (! static::runningInSail()) {
            static::startChromeDriver();
        }
    }
    
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
        ])->unless($this->hasHeadlessDisabled(), function ($items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());
        
        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
```

### 6.2 カスタマージャーニーテスト
```php
// tests/Browser/CustomerOrderFlowTest.php
class CustomerOrderFlowTest extends DuskTestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function customer_can_complete_full_order_flow()
    {
        // テストデータ準備
        $store = Store::factory()->create();
        $session = Session::factory()->active()->create(['store_id' => $store->id]);
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);
        $menuItem = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'ハンバーガーセット',
            'price' => 1200,
            'is_available' => true,
        ]);
        
        $this->browse(function (Browser $browser) use ($session, $menuItem) {
            $browser->visit("/qr/{$session->qr_code}")
                    // QRコード読み取り後の画面
                    ->waitForText('メニュー')
                    ->assertSee('ハンバーガーセット')
                    ->assertSee('¥1,200')
                    
                    // 商品をカートに追加
                    ->click('@add-to-cart-' . $menuItem->id)
                    ->waitForText('カートに追加されました')
                    
                    // カートを開く
                    ->click('@cart-button')
                    ->waitForText('ハンバーガーセット')
                    ->assertSee('数量: 1')
                    ->assertSee('小計: ¥1,200')
                    
                    // 注文確定
                    ->click('@checkout-button')
                    ->waitForText('注文を確定しますか？')
                    ->click('@confirm-order')
                    
                    // 注文完了画面
                    ->waitForText('注文が完了しました')
                    ->assertSee('注文番号')
                    ->assertSee('合計金額: ¥1,200');
        });
        
        // データベースに注文が記録されていることを確認
        $this->assertDatabaseHas('orders', [
            'session_id' => $session->id,
            'status' => 'pending',
            'total_amount' => 1200,
        ]);
    }
    
    /** @test */
    public function customer_sees_error_when_item_becomes_unavailable()
    {
        $session = Session::factory()->active()->create();
        $menuItem = Product::factory()->create(['is_available' => true]);
        
        $this->browse(function (Browser $browser) use ($session, $menuItem) {
            $browser->visit("/qr/{$session->qr_code}")
                    ->click('@add-to-cart-' . $menuItem->id)
                    ->waitForText('カートに追加されました');
            
            // 商品を在庫切れにする
            $menuItem->update(['is_available' => false]);
            
            $browser->click('@cart-button')
                    ->click('@checkout-button')
                    
                    // エラーメッセージが表示される
                    ->waitForText('申し訳ございません。この商品は現在売り切れです。')
                    ->assertDontSee('注文を確定しますか？');
        });
    }
}
```

## 7. テストデータ管理

### 7.1 Factoryの活用
```php
// database/factories/ProductFactory.php
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'category_id' => MenuCategory::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(300, 2000),
            'image_url' => $this->faker->imageUrl(300, 300, 'food'),
            'is_available' => true,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
    
    public function unavailable(): static
    {
        return $this->state(fn () => ['is_available' => false]);
    }
    
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
    
    public function withOptions(): static
    {
        return $this->afterCreating(function (Product $menuItem) {
            $option = ProductOption::factory()->create([
                'product_id' => $menuItem->id
            ]);
            
            ProductOptionValue::factory()->count(3)->create([
                'option_id' => $option->id
            ]);
        });
    }
}
```

### 7.2 テスト用Seeder
```php
// database/seeders/TestDataSeeder.php
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // テスト用店舗
        $store = Store::factory()->create([
            'name' => 'テスト店舗',
            'slug' => 'test-store',
        ]);
        
        // テスト用カテゴリ
        $categories = MenuCategory::factory()->count(3)->create([
            'store_id' => $store->id,
        ]);
        
        // 各カテゴリにメニューアイテムを作成
        foreach ($categories as $category) {
            Product::factory()->count(5)->create([
                'store_id' => $store->id,
                'category_id' => $category->id,
            ]);
        }
        
        // テスト用セッション
        Session::factory()->count(3)->active()->create([
            'store_id' => $store->id,
        ]);
        
        // テスト用ユーザー
        User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
            'store_id' => $store->id,
        ]);
    }
}
```

## 8. テストカバレッジ

### 8.1 カバレッジ測定
```bash
# カバレッジレポート生成
php artisan test --coverage

# HTMLレポート生成
php artisan test --coverage-html coverage-report
```

### 8.2 カバレッジ目標
- **全体**: 80%以上
- **Model**: 90%以上
- **Service**: 85%以上
- **Controller**: 75%以上

## 9. パフォーマンステスト

### 9.1 レスポンス時間測定
```php
// tests/Performance/ApiPerformanceTest.php
class ApiPerformanceTest extends TestCase
{
    /** @test */
    public function menu_api_responds_within_acceptable_time()
    {
        Product::factory()->count(100)->create();
        
        $startTime = microtime(true);
        
        $response = $this->getJson('/api/v1/menu-items');
        
        $responseTime = (microtime(true) - $startTime) * 1000; // ms
        
        $response->assertOk();
        $this->assertLessThan(500, $responseTime, 'Menu API should respond within 500ms');
    }
    
    /** @test */
    public function order_creation_handles_concurrent_requests()
    {
        $session = Session::factory()->active()->create();
        $menuItem = Product::factory()->create();
        $user = User::factory()->Product::factory()->create();
        
        $promises = [];
        
        // 同時に10回注文リクエストを送信
        for ($i = 0; $i < 10; $i++) {
            $promises[] = $this->actingAs($user, 'sanctum')
                ->postJson('/api/v1/orders', [
                    'session_id' => $session->id,
                    'items' => [[
                        'product_id' => $menuItem->id,
                        'quantity' => 1,
                    ]]
                ]);
        }
        
        // 全てのリクエストが成功することを確認
        foreach ($promises as $response) {
            $response->assertStatus(201);
        }
        
        // 注文が正しく作成されていることを確認
        $this->assertEquals(10, Order::count());
    }
}
```

## 10. CI/CDでのテスト実行

### 10.1 GitHub Actions設定
```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ALLOW_EMPTY_PASSWORD: yes
          MYSQL_DATABASE: mobile_order_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
    
    - name: Install dependencies
      run: |
        composer install --no-dev --optimize-autoloader
        npm ci
        npm run build
    
    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.testing', '.env');"
    
    - name: Generate key
      run: php artisan key:generate
    
    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache
    
    - name: Run tests
      run: |
        php artisan test --coverage --min=80
        php artisan dusk:chrome-driver
        php artisan dusk
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: mobile_order_test
        DB_USERNAME: root
        DB_PASSWORD: ''
```

## 11. テスト実行コマンド

### 11.1 基本コマンド
```bash
# 全テスト実行
php artisan test

# 特定のテストスイート実行
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# 特定のテストクラス実行
php artisan test tests/Unit/Models/ProductTest.php

# 特定のテストメソッド実行
php artisan test --filter it_calculates_total_price_with_options

# 並列実行（高速化）
php artisan test --parallel

# カバレッジ付き実行
php artisan test --coverage

# E2Eテスト実行
php artisan dusk
```

### 11.2 デバッグコマンド
```bash
# 詳細出力
php artisan test --verbose

# 失敗時に停止
php artisan test --stop-on-failure

# 警告も表示
php artisan test --display-warnings
```

---

このテスト設計書に従って実装することで、品質の高いアプリケーションを継続的に開発・保守できます。特に、自動テストによる品質保証とCI/CDでの継続的な検証が重要です。