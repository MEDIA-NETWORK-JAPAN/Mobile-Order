<?php

namespace Database\Factories;

use App\Models\Option;
use App\Models\OptionDetail;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OptionDetail>
 */
class OptionDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'option_id' => Option::factory(),
            'product_id' => Product::factory(),
            'default_selected' => $this->faker->boolean(20), // 20%の確率でデフォルト選択
            'sort_order' => $this->faker->numberBetween(0, 99),
        ];
    }

    /**
     * 特定のオプションに関連付け
     */
    public function forOption($optionId): static
    {
        return $this->state(fn (array $attributes) => [
            'option_id' => $optionId,
        ]);
    }

    /**
     * 特定の商品に関連付け
     */
    public function forProduct($productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * デフォルト選択状態
     */
    public function defaultSelected(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_selected' => true,
        ]);
    }

    /**
     * 非デフォルト選択状態
     */
    public function notDefaultSelected(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_selected' => false,
        ]);
    }
}
