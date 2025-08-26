<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => $this->generateCategoryName(),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    /**
     * 非アクティブカテゴリ状態
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * 特定店舗のカテゴリ
     */
    public function forStore($storeId): static
    {
        return $this->state(fn () => ['store_id' => $storeId]);
    }

    /**
     * 表示順を指定
     */
    public function withSortOrder(int $order): static
    {
        return $this->state(fn () => ['sort_order' => $order]);
    }

    /**
     * メインカテゴリセット（日本の飲食店でよくあるカテゴリ）
     */
    public function mainCategories(): static
    {
        static $index = 0;
        $categories = [
            'メイン料理',
            'サイドメニュー', 
            'ドリンク',
            'デザート',
            'セットメニュー'
        ];
        
        $name = $categories[$index % count($categories)];
        $index++;
        
        return $this->state(fn () => [
            'name' => $name,
            'sort_order' => $index,
        ]);
    }

    /**
     * カテゴリ名生成ヘルパー
     */
    private function generateCategoryName(): string
    {
        $categories = [
            // メイン系
            'ラーメン', 'パスタ', 'ピザ', 'ハンバーガー', 'カレー',
            'うどん・そば', '丼物', '定食', 'ステーキ', '魚料理',
            
            // サイド系
            'サラダ', '前菜', 'おつまみ', 'スープ', 'サイドメニュー',
            '揚げ物', '焼き物', '一品料理',
            
            // ドリンク系
            'ソフトドリンク', 'アルコール', 'コーヒー', '紅茶', 'ジュース',
            'ビール', 'ワイン', '日本酒', '焼酎',
            
            // デザート系
            'デザート', 'ケーキ', 'アイス', '和菓子', '洋菓子',
            
            // セット系
            'ランチセット', 'ディナーセット', 'ファミリーセット', 'お得セット'
        ];
        
        return $this->faker->randomElement($categories);
    }
}