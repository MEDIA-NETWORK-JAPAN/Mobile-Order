<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->admin = User::factory()->admin()->forStore($this->store->id)->create();
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_access_dashboard(): void
    {
        $staff = User::factory()->staff()->forStore($this->store->id)->create();

        $response = $this->actingAs($staff)
            ->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_dashboard_displays_statistics_for_super_admin(): void
    {
        // テストデータ作成
        Product::factory()->forStore($this->store->id)->count(10)->create();
        Order::factory()->today()->forStore($this->store->id)->count(5)->create();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas(['todayOrders', 'activeProducts', 'totalProducts']);
    }

    public function test_dashboard_displays_statistics_for_admin(): void
    {
        // 自店舗のデータ
        Product::factory()->forStore($this->store->id)->count(5)->create();
        Order::factory()->today()->forStore($this->store->id)->count(3)->create();

        // 他店舗のデータ（表示されないはず）
        $otherStore = Store::factory()->create();
        Product::factory()->forStore($otherStore->id)->count(10)->create();
        Order::factory()->today()->forStore($otherStore->id)->count(10)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas(['todayOrders', 'activeProducts', 'totalProducts']);

        // 自店舗のデータのみ表示されることを確認
        $this->assertEquals(3, $response->viewData('todayOrders'));
    }

    public function test_dashboard_shows_growth_calculations(): void
    {
        // 昨日のデータ
        Order::factory()->yesterday()->forStore($this->store->id)->count(2)->create([
            'total_amount' => 1000,
        ]);

        // 今日のデータ
        Order::factory()->today()->forStore($this->store->id)->count(4)->create([
            'total_amount' => 1500,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas(['orderGrowth', 'salesGrowth']);

        // 成長率計算の確認
        $orderGrowth = $response->viewData('orderGrowth');
        $this->assertStringContainsString('+', $orderGrowth);
    }

    public function test_dashboard_shows_recent_orders(): void
    {
        // 最新の注文を作成
        $orders = Order::factory()->today()->forStore($this->store->id)->count(7)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('recentOrders');

        // 最新5件のみ表示されることを確認
        $recentOrders = $response->viewData('recentOrders');
        $this->assertCount(5, $recentOrders);
    }

    public function test_dashboard_shows_popular_products(): void
    {
        // 商品と注文を作成
        $products = Product::factory()->forStore($this->store->id)->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('popularProducts');
    }

    public function test_super_admin_sees_system_status(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('posStatus');
        $response->assertSee('システム状態');
    }

    public function test_regular_admin_does_not_see_system_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('システム状態');
    }
}
