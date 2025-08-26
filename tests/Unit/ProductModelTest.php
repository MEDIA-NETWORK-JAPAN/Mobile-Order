<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $this->category = Category::factory()->forStore($this->store->id)->create();
    }

    public function test_product_belongs_to_store(): void
    {
        $product = Product::factory()->forStore($this->store->id)->create();

        $this->assertInstanceOf(Store::class, $product->store);
        $this->assertEquals($this->store->id, $product->store->id);
    }

    public function test_product_has_many_categories(): void
    {
        $product = Product::factory()->forStore($this->store->id)->create();
        $product->categories()->attach($this->category);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $product->categories);
        $this->assertTrue($product->categories->contains($this->category));
    }

    public function test_product_availability_status_enum(): void
    {
        $validStatuses = ['available', 'sold_out', 'not_arrived', 'preparing'];

        foreach ($validStatuses as $status) {
            $product = Product::factory()->create(['availability_status' => $status]);
            $this->assertEquals($status, $product->availability_status);
        }
    }

    public function test_product_tax_type_enum(): void
    {
        $validTaxTypes = ['standard', 'reduced', 'exempt', 'non_taxable'];

        foreach ($validTaxTypes as $taxType) {
            $product = Product::factory()->create(['tax_type' => $taxType]);
            $this->assertEquals($taxType, $product->tax_type);
        }
    }

    public function test_product_price_can_be_negative(): void
    {
        $product = Product::factory()->create(['price' => -500]);

        $this->assertEquals(-500, $product->price);
        $this->assertTrue($product->price < 0);
    }

    public function test_product_tax_in_price_calculation(): void
    {
        $product = Product::factory()->create([
            'price' => 1000,
            'tax_type' => 'standard',
        ]);

        // 税込価格は自動計算される（10%税率想定）
        $this->assertGreaterThan($product->price, $product->tax_in_price);
    }

    public function test_product_active_scope(): void
    {
        $activeProduct = Product::factory()->create(['is_active' => true]);
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        $activeProducts = Product::active()->get();

        $this->assertTrue($activeProducts->contains($activeProduct));
        $this->assertFalse($activeProducts->contains($inactiveProduct));
    }

    public function test_product_available_scope(): void
    {
        $availableProduct = Product::factory()->available()->create();
        $soldOutProduct = Product::factory()->soldOut()->create();

        $availableProducts = Product::available()->get();

        $this->assertTrue($availableProducts->contains($availableProduct));
        $this->assertFalse($availableProducts->contains($soldOutProduct));
    }

    public function test_product_factory_states(): void
    {
        $availableProduct = Product::factory()->available()->create();
        $soldOutProduct = Product::factory()->soldOut()->create();
        $notArrivedProduct = Product::factory()->notArrived()->create();
        $preparingProduct = Product::factory()->preparing()->create();

        $this->assertEquals('available', $availableProduct->availability_status);
        $this->assertEquals('sold_out', $soldOutProduct->availability_status);
        $this->assertEquals('not_arrived', $notArrivedProduct->availability_status);
        $this->assertEquals('preparing', $preparingProduct->availability_status);
    }

    public function test_product_code_is_unique(): void
    {
        $code = 'UNIQUE001';
        Product::factory()->create(['code' => $code]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Product::factory()->create(['code' => $code]);
    }

    public function test_product_sort_order_default_ordering(): void
    {
        $product1 = Product::factory()->create(['sort_order' => 3]);
        $product2 = Product::factory()->create(['sort_order' => 1]);
        $product3 = Product::factory()->create(['sort_order' => 2]);

        $products = Product::orderBy('sort_order')->get();

        $this->assertEquals($product2->id, $products->first()->id);
        $this->assertEquals($product1->id, $products->last()->id);
    }

    public function test_product_expected_available_time_is_nullable(): void
    {
        $productWithTime = Product::factory()->preparing()->create();
        $productWithoutTime = Product::factory()->available()->create();

        $this->assertNotNull($productWithTime->expected_available_time);
        $this->assertNull($productWithoutTime->expected_available_time);
    }

    public function test_product_belongs_to_correct_store(): void
    {
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        $product1 = Product::factory()->forStore($store1->id)->create();
        $product2 = Product::factory()->forStore($store2->id)->create();

        $this->assertEquals($store1->id, $product1->store_id);
        $this->assertEquals($store2->id, $product2->store_id);
        $this->assertNotEquals($product1->store_id, $product2->store_id);
    }

    public function test_product_cost_is_optional(): void
    {
        $productWithCost = Product::factory()->create(['cost' => 600]);
        $productWithoutCost = Product::factory()->create(['cost' => null]);

        $this->assertEquals(600, $productWithCost->cost);
        $this->assertNull($productWithoutCost->cost);
    }

    public function test_product_description_is_optional(): void
    {
        $productWithDescription = Product::factory()->create(['description' => 'テスト商品説明']);
        $productWithoutDescription = Product::factory()->create(['description' => null]);

        $this->assertEquals('テスト商品説明', $productWithDescription->description);
        $this->assertNull($productWithoutDescription->description);
    }
}
