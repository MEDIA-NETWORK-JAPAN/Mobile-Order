<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private Store $store;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->admin = User::factory()->admin()->forStore($this->store->id)->create();
        $this->category = Category::factory()->forStore($this->store->id)->create();
    }

    public function test_super_admin_can_view_product_index(): void
    {
        Product::factory()->forStore($this->store->id)->count(3)->create();

        Livewire::actingAs($this->superAdmin)
            ->test('admin.products.product-index')
            ->assertViewHas('canEdit', true)
            ->assertSee('商品管理');
    }

    public function test_admin_can_view_product_index_but_cannot_edit(): void
    {
        Product::factory()->forStore($this->store->id)->count(3)->create();

        Livewire::actingAs($this->admin)
            ->test('admin.products.product-index')
            ->assertViewHas('canEdit', false)
            ->assertSee('商品管理')
            ->assertSee('POS側で管理されています');
    }

    public function test_super_admin_can_create_product(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('admin.products.product-form')
            ->set('store_id', $this->store->id)
            ->set('code', 'TEST001')
            ->set('name', 'テスト商品')
            ->set('description', 'テスト商品の説明')
            ->set('price', 1000)
            ->set('tax_type', 'standard')
            ->set('availability_status', 'available')
            ->set('sort_order', 1)
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'code' => 'TEST001',
            'name' => 'テスト商品',
            'price' => 1000,
        ]);
    }

    public function test_admin_cannot_create_product(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin.products.product-form')
            ->set('store_id', $this->store->id)
            ->set('code', 'TEST001')
            ->set('name', 'テスト商品')
            ->set('price', 1000)
            ->call('save')
            ->assertHasErrors()
            ->assertSee('商品の編集権限がありません');

        $this->assertDatabaseMissing('products', [
            'code' => 'TEST001',
        ]);
    }

    public function test_super_admin_can_edit_existing_product(): void
    {
        $product = Product::factory()->forStore($this->store->id)->create([
            'name' => '元の商品名',
            'price' => 800,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test('admin.products.product-form', ['productId' => $product->id])
            ->set('name', '更新された商品名')
            ->set('price', 1200)
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => '更新された商品名',
            'price' => 1200,
        ]);
    }

    public function test_admin_cannot_edit_existing_product(): void
    {
        $product = Product::factory()->forStore($this->store->id)->create([
            'name' => '元の商品名',
            'price' => 800,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.products.product-form', ['productId' => $product->id])
            ->set('name', '更新された商品名')
            ->set('price', 1200)
            ->call('save')
            ->assertHasErrors()
            ->assertSee('商品の編集権限がありません');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => '元の商品名',
            'price' => 800,
        ]);
    }

    public function test_product_form_shows_correct_ui_for_super_admin(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('admin.products.product-form')
            ->assertViewHas('canEdit', true)
            ->assertSee('SuperAdmin緊急編集モード')
            ->assertDontSee('商品データはPOS側で管理されています');
    }

    public function test_product_form_shows_correct_ui_for_admin(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin.products.product-form')
            ->assertViewHas('canEdit', false)
            ->assertSee('商品データはPOS側で管理されています')
            ->assertDontSee('SuperAdmin緊急編集モード');
    }

    public function test_product_index_filters_by_store_for_admin(): void
    {
        // 自店舗の商品
        $ownProduct = Product::factory()->forStore($this->store->id)->create(['name' => '自店舗商品']);

        // 他店舗の商品
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->forStore($otherStore->id)->create(['name' => '他店舗商品']);

        Livewire::actingAs($this->admin)
            ->test('admin.products.product-index')
            ->assertSee('自店舗商品')
            ->assertDontSee('他店舗商品');
    }

    public function test_super_admin_sees_all_stores_products(): void
    {
        // 複数店舗の商品
        $store1Product = Product::factory()->forStore($this->store->id)->create(['name' => '店舗1商品']);

        $store2 = Store::factory()->create();
        $store2Product = Product::factory()->forStore($store2->id)->create(['name' => '店舗2商品']);

        Livewire::actingAs($this->superAdmin)
            ->test('admin.products.product-index')
            ->assertSee('店舗1商品')
            ->assertSee('店舗2商品');
    }

    public function test_product_validation_works(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('admin.products.product-form')
            ->set('store_id', '')
            ->set('code', '')
            ->set('name', '')
            ->set('price', '')
            ->call('save')
            ->assertHasErrors(['store_id', 'code', 'name', 'price']);
    }

    public function test_product_price_can_be_negative(): void
    {
        // マイナス価格対応（値引き商品）
        Livewire::actingAs($this->superAdmin)
            ->test('admin.products.product-form')
            ->set('store_id', $this->store->id)
            ->set('code', 'DISCOUNT001')
            ->set('name', '値引き商品')
            ->set('price', -500)
            ->set('tax_type', 'standard')
            ->set('availability_status', 'available')
            ->set('sort_order', 1)
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'code' => 'DISCOUNT001',
            'price' => -500,
        ]);
    }
}
