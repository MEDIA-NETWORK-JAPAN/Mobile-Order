<?php

namespace Database\Factories;

use App\Models\Option;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Option>
 */
class OptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $optionNames = [
            '麺の硬さ',
            'スープの濃さ',
            '辛さレベル',
            'トッピング',
            'サイズ',
            '温度',
            'ドレッシング',
            '調理方法',
        ];

        return [
            'store_id' => Store::factory(),
            'title' => $this->faker->randomElement($optionNames),
            'required' => $this->faker->boolean(30), // 30%の確率で必須
            'selection_type' => $this->faker->randomElement(['single', 'multiple']),
            'translations' => [
                'en' => $this->faker->words(2, true),
                'zh_tw' => $this->faker->words(2, true),
                'zh_cn' => $this->faker->words(2, true),
                'ko' => $this->faker->words(2, true),
            ],
        ];
    }

    /**
     * 特定の店舗に関連付け
     */
    public function forStore($storeId): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $storeId,
        ]);
    }

    /**
     * 必須オプション
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'required' => true,
        ]);
    }

    /**
     * オプショナル
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'required' => false,
        ]);
    }

    /**
     * 単一選択
     */
    public function single(): static
    {
        return $this->state(fn (array $attributes) => [
            'selection_type' => 'single',
        ]);
    }

    /**
     * 複数選択
     */
    public function multiple(): static
    {
        return $this->state(fn (array $attributes) => [
            'selection_type' => 'multiple',
        ]);
    }


    /**
     * 麺の硬さオプション
     */
    public function noodleHardness(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => '麺の硬さ',
            'required' => true,
            'selection_type' => 'single',
            'translations' => [
                'en' => 'Noodle Texture',
                'zh_tw' => '麵條硬度',
                'zh_cn' => '面条硬度',
                'ko' => '면의 경도',
            ],
        ]);
    }

    /**
     * トッピングオプション
     */
    public function toppings(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'トッピング',
            'required' => false,
            'selection_type' => 'multiple',
            'translations' => [
                'en' => 'Toppings',
                'zh_tw' => '配菜',
                'zh_cn' => '配菜',
                'ko' => '토핑',
            ],
        ]);
    }
}
