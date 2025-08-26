<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Session>
 */
class SessionFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'qr_code' => $this->generateQrCode(),
            'table_number' => $this->faker->numberBetween(1, 50),
            'mode' => $this->faker->randomElement(['fixed', 'dynamic']),
            'status' => $this->faker->randomElement(['active', 'completed', 'expired']),
            'expires_at' => $this->faker->optional(0.8)->dateTimeBetween('now', '+3 hours'),
            'max_orders' => $this->faker->optional(0.3)->numberBetween(1, 10),
            'completed_at' => null,
        ];
    }

    /**
     * アクティブなセッション
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+3 hours'),
            'completed_at' => null,
        ]);
    }

    /**
     * 完了済みセッション
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
        ]);
    }

    /**
     * 期限切れセッション
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'expires_at' => $this->faker->dateTimeBetween('-3 hours', '-1 hour'),
            'completed_at' => null,
        ]);
    }

    /**
     * 固定モードセッション（QRコード固定運用）
     */
    public function fixed(): static
    {
        return $this->state(fn () => [
            'mode' => 'fixed',
            'expires_at' => null, // 固定モードは無期限
            'max_orders' => null,
        ]);
    }

    /**
     * 都度発行モードセッション
     */
    public function dynamic(): static
    {
        return $this->state(fn () => [
            'mode' => 'dynamic',
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+3 hours'),
            'max_orders' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * 特定店舗のセッション
     */
    public function forStore($storeId): static
    {
        return $this->state(fn () => ['store_id' => $storeId]);
    }

    /**
     * 特定テーブルのセッション
     */
    public function forTable(int $tableNumber): static
    {
        return $this->state(fn () => ['table_number' => $tableNumber]);
    }

    /**
     * QRコード生成ヘルパー
     */
    private function generateQrCode(): string
    {
        return Str::random(12).'-'.Str::random(8);
    }
}
