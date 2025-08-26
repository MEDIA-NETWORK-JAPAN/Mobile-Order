<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     */
    public function definition(): array
    {
        $price = $this->faker->numberBetween(300, 2000);
        $taxRate = $this->faker->randomElement([0.08, 0.10]); // 8%または10%

        return [
            'store_id' => Store::factory(),
            'code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{4}'),
            'name' => $this->generateProductName(),
            'description' => $this->faker->realText(100),
            'price' => $price,
            'tax_type' => $this->faker->randomElement(['standard', 'reduced', 'exempt']),
            'tax_in_price' => round($price * (1 + $taxRate)),
            'cost' => round($price * 0.6), // 原価率60%
            'availability_status' => $this->faker->randomElement(['available', 'sold_out', 'not_arrived', 'preparing']),
            'availability_message' => $this->faker->optional(0.3)->sentence(),
            'expected_available_time' => $this->faker->optional(0.2)->dateTimeBetween('now', '+2 hours'),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    /**
     * 利用可能な商品状態
     */
    public function available(): static
    {
        return $this->state(fn () => [
            'availability_status' => 'available',
            'availability_message' => null,
            'expected_available_time' => null,
        ]);
    }

    /**
     * 売り切れ商品状態
     */
    public function soldOut(): static
    {
        return $this->state(fn () => [
            'availability_status' => 'sold_out',
            'availability_message' => '本日分は売り切れました',
        ]);
    }

    /**
     * 未入荷商品状態
     */
    public function notArrived(): static
    {
        return $this->state(fn () => [
            'availability_status' => 'not_arrived',
            'availability_message' => '入荷待ちです',
        ]);
    }

    /**
     * 準備中商品状態
     */
    public function preparing(): static
    {
        return $this->state(fn () => [
            'availability_status' => 'preparing',
            'availability_message' => '準備中です',
            'expected_available_time' => $this->faker->dateTimeBetween('now', '+1 hour'),
        ]);
    }

    /**
     * 非アクティブ商品状態
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * 特定店舗の商品
     */
    public function forStore($storeId): static
    {
        return $this->state(fn () => ['store_id' => $storeId]);
    }

    /**
     * カテゴリ付きの商品（リレーション設定後）
     */
    public function withCategories(int $count = 1): static
    {
        return $this->afterCreating(function ($product) use ($count) {
            $categories = Category::factory()->count($count)->create([
                'store_id' => $product->store_id,
            ]);
            $product->categories()->attach($categories);
        });
    }

    /**
     * 商品名生成ヘルパー
     */
    private function generateProductName(): string
    {
        $foodTypes = [
            'ラーメン', 'パスタ', 'ハンバーガー', 'ピザ', 'カレー',
            'うどん', 'そば', 'チャーハン', '丼物', 'サンドイッチ',
            'オムライス', 'グラタン', 'リゾット', 'ステーキ', '唐揚げ',
        ];

        $modifiers = [
            '特製', '自家製', '極上', '本格', '濃厚', 'あっさり',
            'スパイシー', 'マイルド', 'ジューシー', 'サクサク',
        ];

        $base = $this->faker->randomElement($foodTypes);
        $modifier = $this->faker->optional(0.7)->randomElement($modifiers);

        return $modifier ? $modifier.$base : $base;
    }
}
