<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected Store $store;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // テストデータ作成
        $this->store = Store::factory()->create();

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'store_id' => $this->store->id,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'store_id' => $this->store->id,
        ]);

        $this->product = Product::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'テスト商品',
            'code' => 'TEST001',
            'price' => 1000,
        ]);
    }

    /** @test */
    public function superadmin_can_view_product_list()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('商品管理');
        $response->assertSee('テスト商品');
    }

    /** @test */
    public function superadmin_can_access_edit_page()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.products.edit', $this->product));

        $response->assertStatus(200);
        $response->assertSee('商品編集');
        $response->assertSee($this->product->name);
    }

    /** @test */
    public function superadmin_can_access_create_page()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.products.create'));

        $response->assertStatus(200);
        $response->assertSee('新規商品追加');
    }

    /** @test */
    public function superadmin_can_update_product_via_livewire()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test('admin.products.product-form', ['product' => $this->product->id])
            ->set('name', '更新後の商品名')
            ->set('price', 2000)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => '更新後の商品名',
            'price' => 2000,
        ]);
    }

    /** @test */
    public function superadmin_can_create_product_via_livewire()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test('admin.products.product-form')
            ->set('store_id', $this->store->id)
            ->set('code', 'NEW001')
            ->set('name', '新規商品')
            ->set('price', 1500)
            ->set('tax_type', 'standard')
            ->set('availability_status', 'available')
            ->set('sort_order', 0)
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'code' => 'NEW001',
            'name' => '新規商品',
            'price' => 1500,
        ]);
    }

    /** @test */
    public function superadmin_can_delete_product_via_livewire()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test('admin.products.product-index')
            ->call('delete', $this->product->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('products', [
            'id' => $this->product->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function normal_admin_cannot_edit_product()
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.products.product-form', ['product' => $this->product->id])
            ->set('name', '編集後の商品名')
            ->call('save')
            ->assertSee('商品の編集権限がありません');

        // データベースが変更されていないことを確認
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => 'テスト商品', // 元の名前のまま
        ]);
    }

    /** @test */
    public function normal_admin_can_view_product_details()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.products.edit', $this->product));

        $response->assertStatus(200);
        $response->assertSee($this->product->name);
        $response->assertSee('商品データはPOS側で管理されています');
    }
}
