<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Option;
use App\Models\OptionDetail;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
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
        ])->map(function ($productData) use ($testStore) {
            return Product::factory()->forStore($testStore->id)->create(array_merge($productData, [
                'description' => $productData['name'].'の説明文です。',
                'tax_in_price' => round($productData['price'] * 1.1),
            ]));
        });

        // サイドメニューの商品
        $sideProducts = collect([
            ['name' => 'チャーシュー', 'price' => 300, 'availability_status' => 'available'],
            ['name' => '半熟煮卵', 'price' => 150, 'availability_status' => 'available'],
            ['name' => 'もやし', 'price' => 100, 'availability_status' => 'available'],
            ['name' => '餃子（5個）', 'price' => 450, 'availability_status' => 'available'],
        ])->map(function ($productData) use ($testStore) {
            return Product::factory()->forStore($testStore->id)->create(array_merge($productData, [
                'description' => $productData['name'].'の説明文です。',
                'tax_in_price' => round($productData['price'] * 1.1),
            ]));
        });

        // ドリンクの商品
        $drinkProducts = collect([
            ['name' => 'ビール', 'price' => 500, 'availability_status' => 'available'],
            ['name' => 'ウーロン茶', 'price' => 200, 'availability_status' => 'available'],
            ['name' => 'コーラ', 'price' => 250, 'availability_status' => 'available'],
        ])->map(function ($productData) use ($testStore) {
            return Product::factory()->forStore($testStore->id)->create(array_merge($productData, [
                'description' => $productData['name'].'の説明文です。',
                'tax_in_price' => round($productData['price'] * 1.1),
            ]));
        });

        // 商品とカテゴリの関連付け
        $ramenProducts->each(fn ($product) => $product->categories()->attach($ramenCategory));
        $sideProducts->each(fn ($product) => $product->categories()->attach($sideCategory));
        $drinkProducts->each(fn ($product) => $product->categories()->attach($drinkCategory));

        // オプション作成
        $noodleHardnessOption = Option::factory()->forStore($testStore->id)->noodleHardness()->create();
        $toppingsOption = Option::factory()->forStore($testStore->id)->toppings()->create();
        
        // 辛さレベルオプション
        $spiceLevelOption = Option::factory()->forStore($testStore->id)->create([
            'title' => '辛さレベル',
            'required' => false,
            'selection_type' => 'single',
            'translations' => [
                'en' => 'Spice Level',
                'zh_tw' => '辛辣程度',
                'zh_cn' => '辣度等级',
                'ko' => '매운 정도',
            ],
        ]);

        // 麺の硬さオプションの選択肢（オプション詳細）
        $noodleHardnessChoices = [
            ['name' => 'やわらかめ', 'sort_order' => 1],
            ['name' => 'ふつう', 'sort_order' => 2, 'default' => true],
            ['name' => 'かため', 'sort_order' => 3],
            ['name' => 'バリかた', 'sort_order' => 4],
        ];

        foreach ($noodleHardnessChoices as $choice) {
            $choiceProduct = Product::factory()->forStore($testStore->id)->create([
                'name' => $choice['name'],
                'code' => 'NOODLE_' . strtoupper(str_replace(['ー', 'っ'], ['_', ''], $choice['name'])),
                'description' => '麺の硬さ: ' . $choice['name'],
                'price' => 0, // オプションは追加料金なし
                'tax_in_price' => 0,
                'availability_status' => 'available',
                'is_active' => true,
            ]);

            OptionDetail::factory()->create([
                'option_id' => $noodleHardnessOption->id,
                'product_id' => $choiceProduct->id,
                'default_selected' => $choice['default'] ?? false,
                'sort_order' => $choice['sort_order'],
            ]);
        }

        // トッピングオプションの選択肢
        $toppingChoices = [
            ['name' => 'チャーシュー', 'price' => 200, 'sort_order' => 1],
            ['name' => '半熟煮卵', 'price' => 100, 'sort_order' => 2],
            ['name' => 'もやし', 'price' => 50, 'sort_order' => 3],
            ['name' => 'ネギ', 'price' => 50, 'sort_order' => 4],
            ['name' => 'のり', 'price' => 30, 'sort_order' => 5],
        ];

        foreach ($toppingChoices as $choice) {
            $choiceProduct = Product::factory()->forStore($testStore->id)->create([
                'name' => $choice['name'],
                'code' => 'TOPPING_' . strtoupper($choice['name']),
                'description' => 'トッピング: ' . $choice['name'],
                'price' => $choice['price'],
                'tax_in_price' => round($choice['price'] * 1.1),
                'availability_status' => 'available',
                'is_active' => true,
            ]);

            OptionDetail::factory()->create([
                'option_id' => $toppingsOption->id,
                'product_id' => $choiceProduct->id,
                'default_selected' => false,
                'sort_order' => $choice['sort_order'],
            ]);
        }

        // 辛さレベルオプションの選択肢
        $spiceLevelChoices = [
            ['name' => 'なし', 'sort_order' => 1, 'default' => true],
            ['name' => '少し辛い', 'sort_order' => 2],
            ['name' => '普通', 'sort_order' => 3],
            ['name' => '辛い', 'sort_order' => 4],
            ['name' => '激辛', 'sort_order' => 5],
        ];

        foreach ($spiceLevelChoices as $choice) {
            $choiceProduct = Product::factory()->forStore($testStore->id)->create([
                'name' => $choice['name'],
                'code' => 'SPICE_' . strtoupper($choice['name']),
                'description' => '辛さ: ' . $choice['name'],
                'price' => 0, // 辛さレベルは追加料金なし
                'tax_in_price' => 0,
                'availability_status' => 'available',
                'is_active' => true,
            ]);

            OptionDetail::factory()->create([
                'option_id' => $spiceLevelOption->id,
                'product_id' => $choiceProduct->id,
                'default_selected' => $choice['default'] ?? false,
                'sort_order' => $choice['sort_order'],
            ]);
        }

        // ラーメンとオプションの関連付け
        foreach ($ramenProducts as $ramenProduct) {
            $ramenProduct->options()->attach([
                $noodleHardnessOption->id => ['sort_order' => 1],
                $toppingsOption->id => ['sort_order' => 2],
                $spiceLevelOption->id => ['sort_order' => 3],
            ]);
        }

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
        $this->command->info('商品数: '.($ramenProducts->count() + $sideProducts->count() + $drinkProducts->count() + 14)); // +14 for option choice products
        $this->command->info('オプション数: 3 (麺の硬さ、トッピング、辛さレベル)');
        $this->command->info('オプション詳細数: 14 (硬さ4種 + トッピング5種 + 辛さ5種)');
    }
}
