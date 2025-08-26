<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * テスト・開発用データの投入
     */
    public function run(): void
    {
        // テスト用店舗作成
        $testStore = Store::factory()->testStore()->create();
        
        // 追加の店舗作成（SuperAdmin用テスト）
        $stores = Store::factory()->count(2)->create();
        
        // テスト用ユーザー作成
        $superAdmin = User::factory()->testSuperAdmin()->create();
        $admin = User::factory()->admin()->forStore($testStore->id)->create([
            'name' => 'テスト管理者',
            'email' => 'admin@example.com',
        ]);
        $staff = User::factory()->staff()->forStore($testStore->id)->create([
            'name' => 'テストスタッフ',
            'email' => 'staff@example.com',
        ]);

        // テスト用カテゴリ作成
        $categories = collect([
            ['name' => 'ラーメン', 'sort_order' => 1],
            ['name' => 'サイドメニュー', 'sort_order' => 2],
            ['name' => 'ドリンク', 'sort_order' => 3],
            ['name' => 'デザート', 'sort_order' => 4],
        ])->map(function ($categoryData) use ($testStore) {
            return Category::factory()->forStore($testStore->id)->create($categoryData);
        });

        // テスト用商品作成
        $ramenCategory = $categories->first();
        $sideCategory = $categories->skip(1)->first();
        $drinkCategory = $categories->skip(2)->first();
        $dessertCategory = $categories->last();

        // ラーメンカテゴリの商品
        $ramenProducts = collect([
            ['name' => '醤油ラーメン', 'price' => 800, 'availability_status' => 'available'],
            ['name' => '味噌ラーメン', 'price' => 850, 'availability_status' => 'available'],
            ['name' => '塩ラーメン', 'price' => 800, 'availability_status' => 'available'],
            ['name' => '特製醤油ラーメン', 'price' => 1200, 'availability_status' => 'available'],
            ['name' => '限定豚骨ラーメン', 'price' => 900, 'availability_status' => 'sold_out'],
        ])->map(function ($productData) use ($testStore, $ramenCategory) {
            return Product::factory()->forStore($testStore->id)->create(array_merge($productData, [
                'description' => $productData['name'] . 'の説明文です。',
                'tax_in_price' => round($productData['price'] * 1.1),
            ]));
        });

        // サイドメニューの商品
        $sideProducts = collect([
            ['name' => 'チャーシュー', 'price' => 300, 'availability_status' => 'available'],
            ['name' => '半熟煮卵', 'price' => 150, 'availability_status' => 'available'],
            ['name' => 'もやし', 'price' => 100, 'availability_status' => 'available'],
            ['name' => '餃子（5個）', 'price' => 450, 'availability_status' => 'available'],
        ])->map(function ($productData) use ($testStore, $sideCategory) {
            return Product::factory()->forStore($testStore->id)->create(array_merge($productData, [
                'description' => $productData['name'] . 'の説明文です。',
                'tax_in_price' => round($productData['price'] * 1.1),
            ]));
        });

        // ドリンクの商品
        $drinkProducts = collect([
            ['name' => 'ビール', 'price' => 500, 'availability_status' => 'available'],
            ['name' => 'ウーロン茶', 'price' => 200, 'availability_status' => 'available'],
            ['name' => 'コーラ', 'price' => 250, 'availability_status' => 'available'],
        ])->map(function ($productData) use ($testStore, $drinkCategory) {
            return Product::factory()->forStore($testStore->id)->create(array_merge($productData, [
                'description' => $productData['name'] . 'の説明文です。',
                'tax_in_price' => round($productData['price'] * 1.1),
            ]));
        });

        // 商品とカテゴリの関連付け
        $ramenProducts->each(fn($product) => $product->categories()->attach($ramenCategory));
        $sideProducts->each(fn($product) => $product->categories()->attach($sideCategory));
        $drinkProducts->each(fn($product) => $product->categories()->attach($drinkCategory));

        // TODO: 未実装機能 - Session/Orderモデル実装後に有効化
        /*
        // テスト用セッション作成
        $activeSessions = Session::factory()->active()->forStore($testStore->id)->count(3)->create();
        $completedSessions = Session::factory()->completed()->forStore($testStore->id)->count(2)->create();
        */

        $this->command->info('基本テストデータの投入が完了しました。');
        $this->command->info("SuperAdmin: {$superAdmin->email} (password: password)");
        $this->command->info("Admin: {$admin->email} (password: password)");
        $this->command->info("Staff: {$staff->email} (password: password)");
        $this->command->info("店舗: {$testStore->name} (ID: {$testStore->id})");
        $this->command->info("カテゴリ数: {$categories->count()}");
        $this->command->info("商品数: " . ($ramenProducts->count() + $sideProducts->count() + $drinkProducts->count()));
    }
}