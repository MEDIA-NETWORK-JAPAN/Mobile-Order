<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
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

    public function test_super_admin_can_view_category_index(): void
    {
        Category::factory()->forStore($this->store->id)->count(3)->create();

        Livewire::actingAs($this->superAdmin)
            ->test('admin.categories.category-index')
            ->assertViewHas('canEdit', true)
            ->assertSee('カテゴリ管理');
    }

    public function test_admin_can_view_category_index_but_cannot_edit(): void
    {
        Category::factory()->forStore($this->store->id)->count(3)->create();

        Livewire::actingAs($this->admin)
            ->test('admin.categories.category-index')
            ->assertViewHas('canEdit', false)
            ->assertSee('カテゴリ管理')
            ->assertSee('POS側で管理されています');
    }

    public function test_super_admin_can_create_category(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('admin.categories.category-create')
            ->set('store_id', $this->store->id)
            ->set('name', 'テストカテゴリ')
            ->set('sort_order', 1)
            ->set('is_active', true)
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'name' => 'テストカテゴリ',
            'store_id' => $this->store->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_create_category(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin.categories.category-create')
            ->set('store_id', $this->store->id)
            ->set('name', 'テストカテゴリ')
            ->set('sort_order', 1)
            ->call('save')
            ->assertHasErrors()
            ->assertSee('カテゴリの編集権限がありません');

        $this->assertDatabaseMissing('categories', [
            'name' => 'テストカテゴリ',
        ]);
    }

    public function test_super_admin_can_edit_category(): void
    {
        $category = Category::factory()->forStore($this->store->id)->create([
            'name' => '元のカテゴリ名',
            'sort_order' => 5,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test('admin.categories.category-index')
            ->call('editCategory', $category->id)
            ->set('editingCategory.name', '更新されたカテゴリ名')
            ->set('editingCategory.sort_order', 10)
            ->call('updateCategory')
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => '更新されたカテゴリ名',
            'sort_order' => 10,
        ]);
    }

    public function test_admin_cannot_edit_category(): void
    {
        $category = Category::factory()->forStore($this->store->id)->create([
            'name' => '元のカテゴリ名',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.categories.category-index')
            ->call('editCategory', $category->id)
            ->assertHasErrors()
            ->assertSee('カテゴリの編集権限がありません');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => '元のカテゴリ名',
        ]);
    }

    public function test_super_admin_can_toggle_category_active_status(): void
    {
        $category = Category::factory()->forStore($this->store->id)->create([
            'is_active' => true,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test('admin.categories.category-index')
            ->call('toggleActive', $category->id)
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_toggle_category_active_status(): void
    {
        $category = Category::factory()->forStore($this->store->id)->create([
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.categories.category-index')
            ->call('toggleActive', $category->id)
            ->assertHasErrors()
            ->assertSee('カテゴリの編集権限がありません');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_delete_category(): void
    {
        $category = Category::factory()->forStore($this->store->id)->create();

        Livewire::actingAs($this->superAdmin)
            ->test('admin.categories.category-index')
            ->call('deleteCategory', $category->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_admin_cannot_delete_category(): void
    {
        $category = Category::factory()->forStore($this->store->id)->create();

        Livewire::actingAs($this->admin)
            ->test('admin.categories.category-index')
            ->call('deleteCategory', $category->id)
            ->assertHasErrors()
            ->assertSee('カテゴリの削除権限がありません');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_create_shows_correct_ui_for_super_admin(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('admin.categories.category-create')
            ->assertViewHas('canEdit', true)
            ->assertSee('SuperAdmin緊急編集モード')
            ->assertDontSee('カテゴリデータはPOS側で管理されています');
    }

    public function test_category_create_shows_correct_ui_for_admin(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin.categories.category-create')
            ->assertViewHas('canEdit', false)
            ->assertSee('カテゴリデータはPOS側で管理されています')
            ->assertDontSee('SuperAdmin緊急編集モード');
    }

    public function test_category_index_filters_by_store_for_admin(): void
    {
        // 自店舗のカテゴリ
        $ownCategory = Category::factory()->forStore($this->store->id)->create(['name' => '自店舗カテゴリ']);

        // 他店舗のカテゴリ
        $otherStore = Store::factory()->create();
        $otherCategory = Category::factory()->forStore($otherStore->id)->create(['name' => '他店舗カテゴリ']);

        Livewire::actingAs($this->admin)
            ->test('admin.categories.category-index')
            ->assertSee('自店舗カテゴリ')
            ->assertDontSee('他店舗カテゴリ');
    }

    public function test_super_admin_sees_all_stores_categories(): void
    {
        // 複数店舗のカテゴリ
        $store1Category = Category::factory()->forStore($this->store->id)->create(['name' => '店舗1カテゴリ']);

        $store2 = Store::factory()->create();
        $store2Category = Category::factory()->forStore($store2->id)->create(['name' => '店舗2カテゴリ']);

        Livewire::actingAs($this->superAdmin)
            ->test('admin.categories.category-index')
            ->assertSee('店舗1カテゴリ')
            ->assertSee('店舗2カテゴリ');
    }

    public function test_category_validation_works(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('admin.categories.category-create')
            ->set('store_id', '')
            ->set('name', '')
            ->set('sort_order', '')
            ->call('save')
            ->assertHasErrors(['store_id', 'name', 'sort_order']);
    }

    public function test_category_sort_order_is_unique_per_store(): void
    {
        // 同じ店舗で同じ表示順のカテゴリを作成
        Category::factory()->forStore($this->store->id)->create(['sort_order' => 1]);

        Livewire::actingAs($this->superAdmin)
            ->test('admin.categories.category-create')
            ->set('store_id', $this->store->id)
            ->set('name', '重複テストカテゴリ')
            ->set('sort_order', 1) // 同じ表示順
            ->call('save')
            ->assertHasErrors(['sort_order']);
    }
}
