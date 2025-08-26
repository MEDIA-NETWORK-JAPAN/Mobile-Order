<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * モデルのデフォルト状態を定義
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'staff',
            'store_id' => \App\Models\Store::factory(),
        ];
    }

    /**
     * 未認証のユーザー
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * SuperAdminユーザー
     */
    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'role' => 'super_admin',
            'store_id' => null,
        ]);
    }

    /**
     * 管理者ユーザー
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
        ]);
    }

    /**
     * スタッフユーザー
     */
    public function staff(): static
    {
        return $this->state(fn () => [
            'role' => 'staff',
        ]);
    }

    /**
     * 特定店舗のユーザー
     */
    public function forStore($storeId): static
    {
        return $this->state(fn () => ['store_id' => $storeId]);
    }

    /**
     * テスト用SuperAdmin
     */
    public function testSuperAdmin(): static
    {
        return $this->state(fn () => [
            'name' => 'テストSuperAdmin',
            'email' => 'superadmin@example.com',
            'role' => 'super_admin',
            'store_id' => null,
        ]);
    }
}
