<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCreateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_super_admin_can_create_product_with_product_create_component()
    {
        // テストデータ作成
        $store = Store::factory()->create();

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'store_id' => $store->id,
        ]);

        // SuperAdminでログイン
        $this->actingAs($superAdmin);

        // ProductCreateコンポーネントをテスト
        $component = Livewire::test('admin.products.product-create')
            ->set('code', 'TEST-CREATE-001')
            ->set('name', 'テスト商品（作成）')
            ->set('price', 1500)
            ->set('availability_status', 'available')
            ->set('sort_order', 10)
            ->set('is_active', true)
            ->call('save');

        // リダイレクト確認
        $component->assertRedirect('/admin/products');

        // データベース確認
        $this->assertDatabaseHas('products', [
            'code' => 'TEST-CREATE-001',
            'name' => 'テスト商品（作成）',
            'price' => 1500,
            'availability_status' => 'available',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function test_admin_cannot_create_product_with_product_create_component()
    {
        // テストデータ作成
        $store = Store::factory()->create();

        $admin = User::factory()->create([
            'role' => 'admin',
            'store_id' => $store->id,
        ]);

        // Adminでログイン
        $this->actingAs($admin);

        // ProductCreateコンポーネントをテスト
        $component = Livewire::test('admin.products.product-create')
            ->set('code', 'TEST-CREATE-002')
            ->set('name', 'テスト商品（作成失敗）')
            ->set('price', 1000)
            ->call('save');

        // エラー確認
        $component->assertHasErrors('permission');

        // データベース確認（商品が作成されていないこと）
        $this->assertDatabaseMissing('products', [
            'code' => 'TEST-CREATE-002',
        ]);
    }
}
