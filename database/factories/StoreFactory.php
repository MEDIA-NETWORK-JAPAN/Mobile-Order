<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('ST???')),
            'name' => $this->faker->company().'店',
            'description' => $this->faker->text(200),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'business_hours' => [
                'monday' => ['open' => '10:00', 'close' => '22:00'],
                'tuesday' => ['open' => '10:00', 'close' => '22:00'],
                'wednesday' => ['open' => '10:00', 'close' => '22:00'],
                'thursday' => ['open' => '10:00', 'close' => '22:00'],
                'friday' => ['open' => '10:00', 'close' => '22:00'],
                'saturday' => ['open' => '10:00', 'close' => '22:00'],
                'sunday' => ['open' => '10:00', 'close' => '22:00'],
            ],
            'qr_mode' => $this->faker->randomElement(['fixed', 'temporary']),
            'is_active' => true,
            'settings' => [
                'tax_rate' => $this->faker->randomFloat(2, 8, 10),
                'service_charge_rate' => $this->faker->randomFloat(2, 0, 15),
                'timezone' => 'Asia/Tokyo',
            ],
        ];
    }

    /**
     * 非アクティブな店舗状態
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * 特定の設定を持つ店舗状態
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn () => ['settings' => $settings]);
    }

    /**
     * テスト用の固定店舗
     */
    public function testStore(): static
    {
        return $this->state(fn () => [
            'code' => 'TEST01',
            'name' => 'テスト店舗',
            'description' => 'テスト用の店舗です',
            'address' => '東京都渋谷区テスト1-2-3',
            'phone' => '03-1234-5678',
            'email' => 'test@example.com',
            'qr_mode' => 'fixed',
        ]);
    }
}
