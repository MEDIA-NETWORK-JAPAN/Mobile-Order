<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_product_edit_page_loads()
    {
        // テストデータ作成
        $store = Store::factory()->create();

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'store_id' => $store->id,
        ]);

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'name' => 'テスト商品ABC',
            'code' => 'TEST001',
            'price' => 1000,
        ]);

        // SuperAdminでログイン
        $this->actingAs($superAdmin);

        // 編集ページにアクセス
        $response = $this->get("/admin/products/{$product->id}/edit");

        // ステータス確認
        $response->assertStatus(200);

        // Livewireコンポーネントの存在確認
        $response->assertSeeLivewire('admin.products.product-form');

        // フォーム要素の確認（商品名のinput要素）
        $response->assertSee('wire:model="name"', false);
        $response->assertSee('value="テスト商品ABC"', false);
    }

    /** @test */
    public function test_product_create_page_loads()
    {
        $store = Store::factory()->create();

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'store_id' => $store->id,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get('/admin/products/create');

        $response->assertStatus(200);
        $response->assertSeeLivewire('admin.products.product-create');
        $response->assertSee('新規商品追加');
    }

    /** @test */
    public function test_normal_admin_sees_view_only_message()
    {
        $store = Store::factory()->create();

        $admin = User::factory()->create([
            'role' => 'admin',
            'store_id' => $store->id,
        ]);

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'name' => 'テスト商品XYZ',
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/products/{$product->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('商品データはPOS側で管理されています');
    }
}
