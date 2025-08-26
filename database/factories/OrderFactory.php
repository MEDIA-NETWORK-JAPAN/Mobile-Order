<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     */
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(500, 5000);
        $taxAmount = round($subtotal * 0.1);
        $serviceCharge = round($subtotal * 0.05);
        $totalAmount = $subtotal + $taxAmount + $serviceCharge;

        return [
            'store_id' => Store::factory(),
            'session_id' => Session::factory(),
            'guest_session_token' => $this->faker->uuid(),
            'order_number' => $this->generateOrderNumber(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled']),
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'service_charge_amount' => $serviceCharge,
            'discount_amount' => $this->faker->optional(0.2)->numberBetween(50, 500) ?? 0,
            'total_amount' => $totalAmount,
            'payment_status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'payment_method' => $this->faker->randomElement(['cash', 'credit_card', 'qr_payment', 'electronic_money']),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'customer_request' => $this->faker->optional(0.4)->sentence(),
            'estimated_ready_time' => $this->faker->dateTimeBetween('now', '+30 minutes'),
            'completed_at' => null,
            'cancelled_at' => null,
            'cloud_synced' => true,
            'pos_synced_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * 待機中の注文
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'payment_status' => 'pending',
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * 確認済みの注文
     */
    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => 'confirmed',
            'payment_status' => 'completed',
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * 準備中の注文
     */
    public function preparing(): static
    {
        return $this->state(fn () => [
            'status' => 'preparing',
            'payment_status' => 'completed',
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * 準備完了の注文
     */
    public function ready(): static
    {
        return $this->state(fn () => [
            'status' => 'ready',
            'payment_status' => 'completed',
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * 完了済みの注文
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'payment_status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'cancelled_at' => null,
        ]);
    }

    /**
     * キャンセル済みの注文
     */
    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'completed_at' => null,
            'cancelled_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * 今日の注文
     */
    public function today(): static
    {
        return $this->state(fn () => [
            'created_at' => $this->faker->dateTimeBetween('today', 'now'),
        ]);
    }

    /**
     * 昨日の注文
     */
    public function yesterday(): static
    {
        return $this->state(fn () => [
            'created_at' => $this->faker->dateTimeBetween('yesterday', 'yesterday 23:59:59'),
        ]);
    }

    /**
     * 特定店舗の注文
     */
    public function forStore($storeId): static
    {
        return $this->state(fn () => ['store_id' => $storeId]);
    }

    /**
     * 注文番号生成ヘルパー
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-'.date('ymd').'-'.str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
