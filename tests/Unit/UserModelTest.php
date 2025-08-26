<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_super_admin(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->isAdmin());
        $this->assertNull($user->store_id);
    }

    public function test_user_can_be_admin(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->admin()->forStore($store->id)->create();

        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->isAdmin());
        $this->assertEquals($store->id, $user->store_id);
    }

    public function test_user_can_be_staff(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->staff()->forStore($store->id)->create();

        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertEquals($store->id, $user->store_id);
    }

    public function test_user_has_store_access_for_own_store(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->admin()->forStore($store->id)->create();

        $this->assertTrue($user->hasStoreAccess($store->id));
    }

    public function test_user_does_not_have_store_access_for_other_store(): void
    {
        $userStore = Store::factory()->create();
        $otherStore = Store::factory()->create();
        $user = User::factory()->admin()->forStore($userStore->id)->create();

        $this->assertFalse($user->hasStoreAccess($otherStore->id));
    }

    public function test_super_admin_has_access_to_all_stores(): void
    {
        $user = User::factory()->superAdmin()->create();
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        $this->assertTrue($user->hasStoreAccess($store1->id));
        $this->assertTrue($user->hasStoreAccess($store2->id));
    }

    public function test_user_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->admin()->forStore($store->id)->create();

        $this->assertInstanceOf(Store::class, $user->store);
        $this->assertEquals($store->id, $user->store->id);
    }

    public function test_super_admin_does_not_belong_to_specific_store(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertNull($user->store);
    }

    public function test_user_role_validation(): void
    {
        $validRoles = ['super_admin', 'admin', 'staff', 'pos_system'];

        foreach ($validRoles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertEquals($role, $user->role);
        }
    }

    public function test_user_password_is_hashed(): void
    {
        $user = User::factory()->create();

        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(password_verify('password', $user->password));
    }

    public function test_user_email_is_unique(): void
    {
        $email = 'unique@example.com';
        User::factory()->create(['email' => $email]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => $email]);
    }

    public function test_user_factory_creates_different_roles(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();

        $this->assertEquals('super_admin', $superAdmin->role);
        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('staff', $staff->role);
    }

    public function test_user_remember_token_is_generated(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->remember_token);
        $this->assertEquals(10, strlen($user->remember_token));
    }

    public function test_user_email_verification(): void
    {
        $verifiedUser = User::factory()->create();
        $unverifiedUser = User::factory()->unverified()->create();

        $this->assertNotNull($verifiedUser->email_verified_at);
        $this->assertNull($unverifiedUser->email_verified_at);
    }
}