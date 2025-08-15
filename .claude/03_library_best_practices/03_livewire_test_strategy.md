# Livewireテスト戦略文書

## 📚 目次

- [1. Livewireテスト概要](#1-livewireテスト概要)
  - [1.1 テスト戦略の目的](#11-テスト戦略の目的)
  - [1.2 テスト分類](#12-テスト分類)
  - [1.3 技術スタック](#13-技術スタック)
- [2. テスト環境セットアップ](#2-テスト環境セットアップ)
  - [2.1 基本設定](#21-基本設定)
  - [2.2 テストベースクラス](#22-テストベースクラス)
- [3. コンポーネント別テスト戦略](#3-コンポーネント別テスト戦略)
  - [3.1 メニューアイテム表示テスト](#31-メニューアイテム表示テスト)
  - [3.2 カート機能テスト](#32-カート機能テスト)
  - [3.3 フォーム処理テスト](#33-フォーム処理テスト)
  - [3.4 認証・認可テスト](#34-認証認可テスト)
- [4. イベント・リスナーテスト](#4-イベントリスナーテスト)
  - [4.1 Livewireイベントテスト](#41-livewireイベントテスト)
  - [4.2 ブラウザイベントテスト](#42-ブラウザイベントテスト)
- [5. 統合テスト](#5-統合テスト)
  - [5.1 データベース連携テスト](#51-データベース連携テスト)
  - [5.2 外部サービス連携テスト](#52-外部サービス連携テスト)
- [6. パフォーマンステスト](#6-パフォーマンステスト)
  - [6.1 クエリ最適化テスト](#61-クエリ最適化テスト)
  - [6.2 メモリ使用量テスト](#62-メモリ使用量テスト)
- [7. エラーハンドリングテスト](#7-エラーハンドリングテスト)
  - [7.1 例外処理テスト](#71-例外処理テスト)
- [8. ブラウザテスト（Laravel Dusk）](#8-ブラウザテストlaravel-dusk)
  - [8.1 JavaScript動作テスト](#81-javascript動作テスト)
- [9. テスト自動化とCI/CD](#9-テスト自動化とcicd)
  - [9.1 GitHub Actions設定](#91-github-actions設定)
  - [9.2 テストコマンド設定](#92-テストコマンド設定)
- [10. ベストプラクティス](#10-ベストプラクティス)
  - [10.1 テスト作成ガイドライン](#101-テスト作成ガイドライン)
  - [10.2 テストデータ管理](#102-テストデータ管理)
  - [10.3 テストの保守性](#103-テストの保守性)

---

## 1. Livewireテスト概要

### 1.1 テスト戦略の目的
Livewireコンポーネントのテストは、ユーザーインタラクション、状態管理、データフロー、リアルタイム更新の品質を保証します。モバイルオーダーシステムにおける重要な機能の動作を確実にテストします。

### 1.2 テスト分類
- **単体テスト**: 個別のLivewireコンポーネントの動作
- **統合テスト**: コンポーネント間の連携とデータベース操作
- **機能テスト**: エンドユーザーの操作フローの検証
- **ブラウザテスト**: 実際のブラウザでのJavaScript動作確認

### 1.3 技術スタック
- **Livewire 3.6+**: テスト対象のコンポーネント
- **PHPUnit 10+**: テストフレームワーク
- **Laravel Dusk**: ブラウザテスト
- **Pest**: テスト記述（オプション）
- **Faker**: テストデータ生成

## 2. テスト環境セットアップ

### 2.1 基本設定

#### `phpunit.xml`設定
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Livewire">
            <directory>tests/Livewire</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
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
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

#### テスト用データベース設定
```php
// config/database.php
'connections' => [
    'testing' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ],
    
    // または分離されたテスト用MySQL
    'mysql_testing' => [
        'driver' => 'mysql',
        'host' => env('DB_TEST_HOST', '127.0.0.1'),
        'port' => env('DB_TEST_PORT', '3306'),
        'database' => env('DB_TEST_DATABASE', 'mobile_order_test'),
        'username' => env('DB_TEST_USERNAME', 'root'),
        'password' => env('DB_TEST_PASSWORD', ''),
    ],
],
```

### 2.2 テストベースクラス

#### Livewireテスト用基底クラス
```php
// tests/TestCase.php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // テスト用の設定
        config(['app.debug' => true]);
        
        // Livewireのテスト設定
        $this->artisan('view:clear');
    }
    
    protected function tearDown(): void
    {
        // テスト後のクリーンアップ
        parent::tearDown();
    }
}
```

#### Livewire専用テストクラス
```php
// tests/LivewireTestCase.php
<?php

namespace Tests;

use Livewire\Livewire;
use Livewire\Component;

abstract class LivewireTestCase extends TestCase
{
    protected function livewire(string $component, array $parameters = [])
    {
        return Livewire::test($component, $parameters);
    }
    
    protected function assertHasNoErrors($component, $field = null)
    {
        if ($field) {
            $this->assertArrayNotHasKey($field, $component->getErrorBag()->toArray());
        } else {
            $this->assertEmpty($component->getErrorBag()->toArray());
        }
    }
    
    protected function assertHasError($component, $field, $message = null)
    {
        $errors = $component->getErrorBag()->get($field);
        $this->assertNotEmpty($errors);
        
        if ($message) {
            $this->assertContains($message, $errors);
        }
    }
}
```

## 3. コンポーネント別テスト戦略

### 3.1 メニューアイテム表示テスト

#### ProductGridコンポーネントテスト
```php
// tests/Livewire/Customer/ProductGridTest.php
<?php

namespace Tests\Livewire\Customer;

use Tests\LivewireTestCase;
use App\Livewire\Customer\ProductGrid;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\WithFaker;

class ProductGridTest extends LivewireTestCase
{
    use WithFaker;
    
    public function test_displays_products()
    {
        // Arrange
        $category = Category::factory()->create(['name' => 'メイン']);
        $items = Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'is_available' => true,
        ]);
        
        // Act & Assert
        $this->livewire(ProductGrid::class)
            ->assertSee($items->first()->name)
            ->assertSee($items->first()->description)
            ->assertSee('¥' . number_format($items->first()->price));
    }
    
    public function test_filters_by_category()
    {
        // Arrange
        $category1 = Category::factory()->create(['name' => 'メイン']);
        $category2 = Category::factory()->create(['name' => 'デザート']);
        
        $mainItem = Product::factory()->create([
            'category_id' => $category1->id,
            'name' => 'ハンバーガー',
            'is_available' => true,
        ]);
        
        $dessertItem = Product::factory()->create([
            'category_id' => $category2->id,
            'name' => 'アイスクリーム',
            'is_available' => true,
        ]);
        
        // Act & Assert
        $this->livewire(ProductGrid::class, ['categoryId' => $category1->id])
            ->assertSee($mainItem->name)
            ->assertDontSee($dessertItem->name);
    }
    
    public function test_searches_products()
    {
        // Arrange
        Product::factory()->create([
            'name' => 'チーズバーガー',
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'name' => 'フライドポテト',
            'is_available' => true,
        ]);
        
        // Act & Assert
        $this->livewire(ProductGrid::class)
            ->set('searchTerm', 'チーズ')
            ->assertSee('チーズバーガー')
            ->assertDontSee('フライドポテト');
    }
    
    public function test_hides_unavailable_items()
    {
        // Arrange
        $availableItem = Product::factory()->create([
            'name' => '利用可能商品',
            'is_available' => true,
        ]);
        
        $unavailableItem = Product::factory()->create([
            'name' => '利用不可商品',
            'is_available' => false,
        ]);
        
        // Act & Assert
        $this->livewire(ProductGrid::class)
            ->assertSee($availableItem->name)
            ->assertDontSee($unavailableItem->name);
    }
    
    public function test_adds_item_to_cart()
    {
        // Arrange
        $item = Product::factory()->create(['is_available' => true]);
        
        // Act & Assert
        $this->livewire(ProductGrid::class)
            ->call('addToCart', $item->id)
            ->assertDispatched('item-added-to-cart', menuItemId: $item->id)
            ->assertSet('flash.message', 'カートに追加されました');
    }
    
    public function test_cannot_add_unavailable_item_to_cart()
    {
        // Arrange
        $item = Product::factory()->create(['is_available' => false]);
        
        // Act & Assert
        $this->livewire(ProductGrid::class)
            ->call('addToCart', $item->id)
            ->assertNotDispatched('item-added-to-cart')
            ->assertSet('flash.error', 'BIZ-STK-001');
    }
    
    public function test_polling_refreshes_menu()
    {
        // Arrange
        $this->livewire(ProductGrid::class)
            ->assertMethodWiredToRefresh('$refresh');
    }
}
```

### 3.2 カート機能テスト

#### CartDrawerコンポーネントテスト
```php
// tests/Livewire/Customer/CartDrawerTest.php
<?php

namespace Tests\Livewire\Customer;

use Tests\LivewireTestCase;
use App\Livewire\Customer\CartDrawer;
use App\Models\Product;
use Illuminate\Support\Facades\Session;

class CartDrawerTest extends LivewireTestCase
{
    public function test_displays_empty_cart_message()
    {
        $this->livewire(CartDrawer::class)
            ->assertSee('カートは空です');
    }
    
    public function test_adds_item_to_cart()
    {
        // Arrange
        $item = Product::factory()->create([
            'name' => 'ハンバーガー',
            'price' => 500,
            'is_available' => true,
        ]);
        
        // Act & Assert
        $this->livewire(CartDrawer::class)
            ->call('addItem', $item->id)
            ->assertSet('items.0.id', $item->id)
            ->assertSet('items.0.name', $item->name)
            ->assertSet('items.0.price', $item->price)
            ->assertSet('items.0.quantity', 1)
            ->assertSet('items.0.total_price', $item->price)
            ->assertSet('isOpen', true);
    }
    
    public function test_increases_quantity_for_existing_item()
    {
        // Arrange
        $item = Product::factory()->create(['price' => 500, 'is_available' => true]);
        
        // Act & Assert
        $this->livewire(CartDrawer::class)
            ->call('addItem', $item->id, 2)
            ->call('addItem', $item->id, 1)
            ->assertCount('items', 1)
            ->assertSet('items.0.quantity', 3)
            ->assertSet('items.0.total_price', 1500);
    }
    
    public function test_removes_item_from_cart()
    {
        // Arrange
        $item = Product::factory()->create(['is_available' => true]);
        
        // Act & Assert
        $this->livewire(CartDrawer::class)
            ->call('addItem', $item->id)
            ->call('removeItem', 0)
            ->assertCount('items', 0);
    }
    
    public function test_calculates_total_price()
    {
        // Arrange
        $item1 = Product::factory()->create(['price' => 500, 'is_available' => true]);
        $item2 = Product::factory()->create(['price' => 300, 'is_available' => true]);
        
        // Act & Assert
        $component = $this->livewire(CartDrawer::class)
            ->call('addItem', $item1->id, 2)
            ->call('addItem', $item2->id, 1);
        
        $this->assertEquals(1300, $component->totalPrice);
    }
    
    public function test_toggles_drawer_visibility()
    {
        $this->livewire(CartDrawer::class)
            ->assertSet('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('toggle')
            ->assertSet('isOpen', false);
    }
    
    public function test_persists_cart_in_session()
    {
        // Arrange
        $item = Product::factory()->create(['is_available' => true]);
        
        // Act
        $this->livewire(CartDrawer::class)
            ->call('addItem', $item->id);
        
        // Assert
        $this->assertNotEmpty(Session::get('cart'));
        $this->assertEquals($item->id, Session::get('cart')[0]['id']);
    }
    
    public function test_loads_cart_from_session_on_mount()
    {
        // Arrange
        $item = Product::factory()->create(['price' => 500, 'is_available' => true]);
        Session::put('cart', [
            [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => 2,
                'total_price' => 1000,
                'image_url' => $item->image_url,
            ]
        ]);
        
        // Act & Assert
        $this->livewire(CartDrawer::class)
            ->assertCount('items', 1)
            ->assertSet('items.0.quantity', 2);
    }
    
    public function test_checkout_process()
    {
        // Arrange
        $item = Product::factory()->create(['is_available' => true]);
        
        // Act & Assert
        $this->livewire(CartDrawer::class)
            ->call('addItem', $item->id)
            ->call('checkout')
            ->assertRedirect('/checkout');
    }
}
```

### 3.3 フォーム処理テスト

#### ProductFormコンポーネントテスト
```php
// tests/Livewire/Admin/ProductFormTest.php
<?php

namespace Tests\Livewire\Admin;

use Tests\LivewireTestCase;
use App\Livewire\Admin\ProductForm;
use App\Models\Product;
use App\Models\Category;

class ProductFormTest extends LivewireTestCase
{
    public function test_creates_new_menu_item()
    {
        // Arrange
        $category = Category::factory()->create();
        
        // Act & Assert
        $this->livewire(ProductForm::class)
            ->set('name', 'テストバーガー')
            ->set('description', 'テスト用の商品説明')
            ->set('price', 800)
            ->set('category_id', $category->id)
            ->set('is_available', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect('/admin/menu-items');
        
        // データベース確認
        $this->assertDatabaseHas('products', [
            'name' => 'テストバーガー',
            'description' => 'テスト用の商品説明',
            'price' => 800,
            'category_id' => $category->id,
            'is_available' => true,
        ]);
    }
    
    public function test_updates_existing_menu_item()
    {
        // Arrange
        $category = Category::factory()->create();
        $item = Product::factory()->create([
            'name' => '元の名前',
            'price' => 500
        ]);
        
        // Act & Assert
        $this->livewire(ProductForm::class, ['item' => $item])
            ->set('name', '更新された名前')
            ->set('price', 600)
            ->call('save')
            ->assertHasNoErrors();
        
        // データベース確認
        $this->assertDatabaseHas('products', [
            'id' => $item->id,
            'name' => '更新された名前',
            'price' => 600,
        ]);
    }
    
    public function test_validates_required_fields()
    {
        $this->livewire(ProductForm::class)
            ->set('name', '')
            ->set('description', '')
            ->set('price', '')
            ->call('save')
            ->assertHasError('name', 'required')
            ->assertHasError('description', 'required')
            ->assertHasError('price', 'required');
    }
    
    public function test_validates_price_is_numeric()
    {
        $this->livewire(ProductForm::class)
            ->set('price', 'invalid')
            ->call('save')
            ->assertHasError('price', 'numeric');
    }
    
    public function test_validates_price_is_positive()
    {
        $this->livewire(ProductForm::class)
            ->set('price', -100)
            ->call('save')
            ->assertHasError('price', 'min');
    }
    
    public function test_validates_category_exists()
    {
        $this->livewire(ProductForm::class)
            ->set('category_id', 999)
            ->call('save')
            ->assertHasError('category_id', 'exists');
    }
    
    public function test_validates_name_max_length()
    {
        $this->livewire(ProductForm::class)
            ->set('name', str_repeat('a', 256))
            ->call('save')
            ->assertHasError('name', 'max');
    }
    
    public function test_displays_error_on_save_failure()
    {
        // データベースエラーをシミュレート
        $this->mock(Product::class, function ($mock) {
            $mock->shouldReceive('save')->andThrow(new \Exception('Database error'));
        });
        
        $category = Category::factory()->create();
        
        $this->livewire(ProductForm::class)
            ->set('name', 'テスト商品')
            ->set('description', 'テスト説明')
            ->set('price', 500)
            ->set('category_id', $category->id)
            ->call('save')
            ->assertSet('flash.error', '保存中にエラーが発生しました');
    }
}
```

### 3.4 認証・認可テスト

#### 認証が必要なコンポーネントのテスト
```php
// tests/Livewire/Admin/AdminDashboardTest.php
<?php

namespace Tests\Livewire\Admin;

use Tests\LivewireTestCase;
use App\Livewire\Admin\AdminDashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;

class AdminDashboardTest extends LivewireTestCase
{
    use WithFaker;
    
    public function test_requires_authentication()
    {
        $this->livewire(AdminDashboard::class)
            ->assertRedirect('/login');
    }
    
    public function test_requires_admin_role()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'staff']);
        
        // Act & Assert
        $this->actingAs($user)
            ->livewire(AdminDashboard::class)
            ->assertForbidden();
    }
    
    public function test_allows_admin_access()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Act & Assert
        $this->actingAs($admin)
            ->livewire(AdminDashboard::class)
            ->assertOk();
    }
    
    public function test_displays_admin_statistics()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Act & Assert
        $this->actingAs($admin)
            ->livewire(AdminDashboard::class)
            ->assertSee('売上統計')
            ->assertSee('注文件数')
            ->assertSee('商品数');
    }
}
```

## 4. イベント・リスナーテスト

### 4.1 Livewireイベントテスト
```php
// tests/Livewire/EventTest.php
<?php

namespace Tests\Livewire;

use Tests\LivewireTestCase;
use App\Livewire\Customer\ProductGrid;
use App\Livewire\Customer\CartDrawer;
use App\Models\Product;

class EventTest extends LivewireTestCase
{
    public function test_menu_grid_dispatches_add_to_cart_event()
    {
        // Arrange
        $item = Product::factory()->create(['is_available' => true]);
        
        // Act & Assert
        $this->livewire(ProductGrid::class)
            ->call('addToCart', $item->id)
            ->assertDispatched('item-added-to-cart', menuItemId: $item->id);
    }
    
    public function test_cart_drawer_listens_to_add_to_cart_event()
    {
        // Arrange
        $item = Product::factory()->create(['is_available' => true]);
        
        // Act & Assert
        $this->livewire(CartDrawer::class)
            ->dispatch('item-added-to-cart', menuItemId: $item->id)
            ->assertCount('items', 1)
            ->assertSet('items.0.id', $item->id);
    }
    
    public function test_cart_counter_updates_on_cart_change()
    {
        // Arrange
        $item = Product::factory()->create(['is_available' => true]);
        
        // Act & Assert
        $this->livewire(CartDrawer::class)
            ->call('addItem', $item->id)
            ->assertDispatched('cart-updated', count: 1);
    }
    
    public function test_toggle_cart_event()
    {
        $this->livewire(CartDrawer::class)
            ->dispatch('toggle-cart')
            ->assertSet('isOpen', true);
    }
}
```

### 4.2 ブラウザイベントテスト
```php
// tests/Livewire/BrowserEventTest.php
<?php

namespace Tests\Livewire;

use Tests\LivewireTestCase;
use App\Livewire\Customer\ProductGrid;

class BrowserEventTest extends LivewireTestCase
{
    public function test_dispatches_browser_event()
    {
        $this->livewire(ProductGrid::class)
            ->call('showSuccessMessage')
            ->assertDispatchedBrowserEvent('show-toast', [
                'type' => 'success',
                'message' => 'カートに追加されました'
            ]);
    }
}
```

## 5. 統合テスト

### 5.1 データベース連携テスト
```php
// tests/Livewire/Integration/MenuIntegrationTest.php
<?php

namespace Tests\Livewire\Integration;

use Tests\LivewireTestCase;
use App\Livewire\Customer\ProductGrid;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MenuIntegrationTest extends LivewireTestCase
{
    use DatabaseTransactions;
    
    public function test_menu_grid_with_real_database_operations()
    {
        // Arrange
        $category = Category::create(['name' => 'テストカテゴリー']);
        $item = Product::create([
            'name' => 'テスト商品',
            'description' => 'テスト説明',
            'price' => 500,
            'category_id' => $category->id,
            'is_available' => true,
        ]);
        
        // Act & Assert
        $this->livewire(ProductGrid::class)
            ->assertSee($item->name)
            ->assertSee($category->name);
        
        // データベース状態確認
        $this->assertDatabaseHas('products', [
            'id' => $item->id,
            'name' => 'テスト商品',
        ]);
    }
    
    public function test_search_with_database_like_query()
    {
        // Arrange
        Product::create([
            'name' => 'チーズバーガー',
            'description' => '美味しいハンバーガー',
            'price' => 600,
            'category_id' => Category::factory()->create()->id,
            'is_available' => true,
        ]);
        
        Product::create([
            'name' => 'フライドポテト',
            'description' => 'サクサクのポテト',
            'price' => 300,
            'category_id' => Category::factory()->create()->id,
            'is_available' => true,
        ]);
        
        // Act & Assert
        $this->livewire(ProductGrid::class)
            ->set('searchTerm', 'チーズ')
            ->assertSee('チーズバーガー')
            ->assertDontSee('フライドポテト');
    }
}
```

### 5.2 外部サービス連携テスト
```php
// tests/Livewire/Integration/PaymentIntegrationTest.php
<?php

namespace Tests\Livewire\Integration;

use Tests\LivewireTestCase;
use App\Livewire\Customer\Checkout;
use App\Services\PaymentService;
use App\Models\Order;
use Mockery;

class PaymentIntegrationTest extends LivewireTestCase
{
    public function test_payment_service_integration()
    {
        // Arrange
        $paymentServiceMock = Mockery::mock(PaymentService::class);
        $paymentServiceMock->shouldReceive('processPayment')
            ->once()
            ->with(1000, 'card')
            ->andReturn(['success' => true, 'transaction_id' => 'TXN123']);
        
        $this->app->instance(PaymentService::class, $paymentServiceMock);
        
        // Act & Assert
        $this->livewire(Checkout::class)
            ->set('total', 1000)
            ->set('paymentMethod', 'card')
            ->call('processPayment')
            ->assertSet('paymentStatus', 'completed')
            ->assertSet('transactionId', 'TXN123');
    }
    
    public function test_payment_failure_handling()
    {
        // Arrange
        $paymentServiceMock = Mockery::mock(PaymentService::class);
        $paymentServiceMock->shouldReceive('processPayment')
            ->once()
            ->andReturn(['success' => false, 'error' => 'Payment failed']);
        
        $this->app->instance(PaymentService::class, $paymentServiceMock);
        
        // Act & Assert
        $this->livewire(Checkout::class)
            ->set('total', 1000)
            ->set('paymentMethod', 'card')
            ->call('processPayment')
            ->assertSet('paymentStatus', 'failed')
            ->assertSee('Payment failed');
    }
}
```

## 6. パフォーマンステスト

### 6.1 クエリ最適化テスト
```php
// tests/Livewire/Performance/QueryOptimizationTest.php
<?php

namespace Tests\Livewire\Performance;

use Tests\LivewireTestCase;
use App\Livewire\Customer\ProductGrid;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class QueryOptimizationTest extends LivewireTestCase
{
    public function test_menu_grid_query_count()
    {
        // Arrange
        $category = Category::factory()->create();
        Product::factory()->count(10)->create(['category_id' => $category->id]);
        
        // Act & Assert
        DB::enableQueryLog();
        
        $this->livewire(ProductGrid::class)->assertOk();
        
        $queryCount = count(DB::getQueryLog());
        
        // メニューアイテム取得で最大2クエリ（メイン + カテゴリ）
        $this->assertLessThanOrEqual(2, $queryCount, 'N+1クエリ問題が発生している可能性があります');
        
        DB::disableQueryLog();
    }
    
    public function test_large_dataset_performance()
    {
        // Arrange
        $category = Category::factory()->create();
        Product::factory()->count(100)->create(['category_id' => $category->id]);
        
        // Act
        $startTime = microtime(true);
        
        $this->livewire(ProductGrid::class)->assertOk();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Assert - 1秒以内に処理完了
        $this->assertLessThan(1.0, $executionTime, 'パフォーマンスが基準を下回っています');
    }
}
```

### 6.2 メモリ使用量テスト
```php
public function test_memory_usage_for_large_cart()
{
    // Arrange
    $items = Product::factory()->count(50)->create(['is_available' => true]);
    
    $initialMemory = memory_get_usage();
    
    // Act
    $component = $this->livewire(CartDrawer::class);
    
    foreach ($items as $item) {
        $component->call('addItem', $item->id);
    }
    
    $finalMemory = memory_get_usage();
    $memoryUsed = $finalMemory - $initialMemory;
    
    // Assert - 10MB以内
    $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'メモリ使用量が多すぎます');
}
```

## 7. エラーハンドリングテスト

### 7.1 例外処理テスト
```php
// tests/Livewire/ErrorHandling/ExceptionHandlingTest.php
<?php

namespace Tests\Livewire\ErrorHandling;

use Tests\LivewireTestCase;
use App\Livewire\Customer\ProductGrid;
use App\Models\Product;
use App\Exceptions\BusinessException;

class ExceptionHandlingTest extends LivewireTestCase
{
    public function test_handles_business_exception()
    {
        // Arrange
        $this->mock(Product::class, function ($mock) {
            $mock->shouldReceive('findOrFail')
                ->andThrow(new BusinessException('BIZ-STK-001', '在庫不足です'));
        });
        
        // Act & Assert
        $this->livewire(ProductGrid::class)
            ->call('addToCart', 1)
            ->assertSet('flash.error', '在庫不足です');
    }
    
    public function test_handles_validation_errors()
    {
        $this->livewire(ProductForm::class)
            ->set('name', '')
            ->set('price', 'invalid')
            ->call('save')
            ->assertHasError('name')
            ->assertHasError('price')
            ->assertNotSet('flash.success');
    }
    
    public function test_handles_database_connection_error()
    {
        // データベース接続エラーをシミュレート
        DB::shouldReceive('connection')->andThrow(new \Exception('Connection failed'));
        
        $this->livewire(ProductGrid::class)
            ->assertSee('システムエラーが発生しました');
    }
}
```

## 8. ブラウザテスト（Laravel Dusk）

### 8.1 JavaScript動作テスト
```php
// tests/Browser/LivewireInteractionTest.php
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Product;
use App\Models\User;

class LivewireInteractionTest extends DuskTestCase
{
    public function test_add_to_cart_with_javascript()
    {
        $item = Product::factory()->create(['is_available' => true]);
        
        $this->browse(function (Browser $browser) use ($item) {
            $browser->visit('/menu')
                ->waitFor('@menu-item-' . $item->id)
                ->click('@add-to-cart-' . $item->id)
                ->waitFor('@cart-drawer')
                ->assertSee($item->name)
                ->assertSee('¥' . number_format($item->price));
        });
    }
    
    public function test_real_time_search()
    {
        Product::factory()->create(['name' => 'チーズバーガー']);
        Product::factory()->create(['name' => 'フライドポテト']);
        
        $this->browse(function (Browser $browser) {
            $browser->visit('/menu')
                ->type('@search-input', 'チーズ')
                ->waitUntilMissing('@loading-spinner')
                ->assertSee('チーズバーガー')
                ->assertDontSee('フライドポテト');
        });
    }
    
    public function test_modal_interactions()
    {
        $item = Product::factory()->create();
        
        $this->browse(function (Browser $browser) use ($item) {
            $browser->visit('/menu')
                ->click('@view-details-' . $item->id)
                ->waitFor('@item-modal')
                ->assertSee($item->name)
                ->assertSee($item->description)
                ->click('@close-modal')
                ->waitUntilMissing('@item-modal');
        });
    }
}
```

## 9. テスト自動化とCI/CD

### 9.1 GitHub Actions設定
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
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: mobile_order_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.3
        extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Generate key
      run: php artisan key:generate
    
    - name: Directory Permissions
      run: chmod -R 755 storage bootstrap/cache
    
    - name: Run Livewire Tests
      run: php artisan test --testsuite=Livewire --coverage-clover=coverage.xml
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: mobile_order_test
        DB_USERNAME: root
        DB_PASSWORD: password
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
        flags: livewire
        name: livewire-coverage
```

### 9.2 テストコマンド設定
```php
// composer.json
{
    "scripts": {
        "test": "php artisan test",
        "test:livewire": "php artisan test --testsuite=Livewire",
        "test:feature": "php artisan test --testsuite=Feature",
        "test:unit": "php artisan test --testsuite=Unit",
        "test:coverage": "php artisan test --coverage-html coverage",
        "test:parallel": "php artisan test --parallel"
    }
}
```

## 10. ベストプラクティス

### 10.1 テスト作成ガイドライン

#### テストの命名規則
```php
// 良い例
public function test_adds_item_to_cart_successfully()
public function test_validates_required_fields()
public function test_displays_error_when_item_unavailable()

// 悪い例
public function testCart()
public function test1()
public function checkValidation()
```

#### Arrange-Act-Assert パターン
```php
public function test_example()
{
    // Arrange - テストデータの準備
    $item = Product::factory()->create();
    
    // Act - テスト対象の実行
    $result = $this->livewire(ProductGrid::class)
        ->call('addToCart', $item->id);
    
    // Assert - 結果の検証
    $result->assertDispatched('item-added-to-cart');
}
```

### 10.2 テストデータ管理

#### ファクトリーの活用
```php
// database/factories/ProductFactory.php
class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(300, 2000),
            'category_id' => Category::factory(),
            'is_available' => true,
            'image_url' => $this->faker->imageUrl(300, 300, 'food'),
        ];
    }
    
    public function unavailable()
    {
        return $this->state(['is_available' => false]);
    }
    
    public function expensive()
    {
        return $this->state(['price' => $this->faker->numberBetween(2000, 5000)]);
    }
}
```

### 10.3 テストの保守性

#### ページオブジェクトパターン
```php
// tests/Support/Pages/MenuPage.php
class MenuPage
{
    public static function addToCart(Browser $browser, $itemId)
    {
        return $browser->click("@add-to-cart-{$itemId}")
            ->waitFor('@cart-notification');
    }
    
    public static function searchFor(Browser $browser, $term)
    {
        return $browser->type('@search-input', $term)
            ->waitUntilMissing('@loading-spinner');
    }
}
```

---

このLivewireテスト戦略文書により、包括的で保守性の高いテストスイートを構築できます。コンポーネントの動作、ユーザーインタラクション、データフロー、パフォーマンスを確実にテストし、高品質なモバイルオーダーシステムを維持できます。