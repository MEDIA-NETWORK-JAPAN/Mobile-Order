<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\TaxRate;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function create()
    {
        $user = auth()->user();

        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect([$user->store]);
        $categories = Category::active()->get();

        return view('admin.products.create', compact('stores', 'categories', 'user'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (! $user->isSuperAdmin()) {
            return back()->withErrors(['permission' => '商品の作成権限がありません。商品マスターデータはPOS側で管理されています。緊急作成にはSuperAdmin権限が必要です。']);
        }

        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'code' => 'required|max:45|unique:products,code',
            'name' => 'required|max:255',
            'description' => 'nullable|max:1000',
            'price' => 'required|integer|min:-999999|max:999999',
            'cost' => 'nullable|integer|min:0',
            'tax_type' => 'required|in:standard,reduced,exempt,non_taxable',
            'availability_status' => 'required|in:available,sold_out,not_arrived,preparing',
            'availability_message' => 'nullable|max:255',
            'expected_available_time' => 'nullable|date_format:H:i',
            'image_url' => 'nullable|max:500|url',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
        ]);

        // 税込価格計算
        $taxRate = TaxRate::getRate($validated['tax_type']);
        $taxInPrice = (int) ($validated['price'] * (1 + $taxRate / 100));

        $product = Product::create([
            'store_id' => $validated['store_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'tax_in_price' => $taxInPrice,
            'cost' => $validated['cost'],
            'tax_type' => $validated['tax_type'],
            'availability_status' => $validated['availability_status'],
            'availability_message' => $validated['availability_message'],
            'expected_available_time' => $validated['expected_available_time'],
            'image_url' => $validated['image_url'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->has('is_active'),
        ]);

        // カテゴリ関連付け
        if (isset($validated['categories'])) {
            $product->categories()->sync($validated['categories']);
        }

        return redirect()->route('admin.products.index')
            ->with('success', '商品を作成しました。');
    }

    public function edit(Product $product)
    {
        $user = auth()->user();

        // 権限チェック
        if (! $user->hasStoreAccess($product->store_id)) {
            return back()->withErrors(['permission' => 'この商品を編集する権限がありません。']);
        }

        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect([$user->store]);
        $categories = Category::active()->get();

        return view('admin.products.edit', compact('product', 'stores', 'categories', 'user'));
    }

    public function update(Request $request, Product $product)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (! $user->isSuperAdmin()) {
            return back()->withErrors(['permission' => '商品の編集権限がありません。商品マスターデータはPOS側で管理されています。緊急編集にはSuperAdmin権限が必要です。']);
        }

        // 権限チェック
        if (! $user->hasStoreAccess($product->store_id)) {
            return back()->withErrors(['permission' => 'この商品を編集する権限がありません。']);
        }

        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'code' => 'required|max:45|unique:products,code,'.$product->id,
            'name' => 'required|max:255',
            'description' => 'nullable|max:1000',
            'price' => 'required|integer|min:-999999|max:999999',
            'cost' => 'nullable|integer|min:0',
            'tax_type' => 'required|in:standard,reduced,exempt,non_taxable',
            'availability_status' => 'required|in:available,sold_out,not_arrived,preparing',
            'availability_message' => 'nullable|max:255',
            'expected_available_time' => 'nullable|date_format:H:i',
            'image_url' => 'nullable|max:500|url',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
        ]);

        // 税込価格計算
        $taxRate = TaxRate::getRate($validated['tax_type']);
        $taxInPrice = (int) ($validated['price'] * (1 + $taxRate / 100));

        $product->update([
            'store_id' => $validated['store_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'tax_in_price' => $taxInPrice,
            'cost' => $validated['cost'],
            'tax_type' => $validated['tax_type'],
            'availability_status' => $validated['availability_status'],
            'availability_message' => $validated['availability_message'],
            'expected_available_time' => $validated['expected_available_time'],
            'image_url' => $validated['image_url'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->has('is_active'),
        ]);

        // カテゴリ関連付け
        if (isset($validated['categories'])) {
            $product->categories()->sync($validated['categories']);
        }

        return redirect()->route('admin.products.index')
            ->with('success', '商品を更新しました。');
    }

    public function destroy(Product $product)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (! $user->isSuperAdmin()) {
            return back()->withErrors(['permission' => '商品の削除権限がありません。商品マスターデータはPOS側で管理されています。緊急削除にはSuperAdmin権限が必要です。']);
        }

        // 権限チェック
        if (! $user->hasStoreAccess($product->store_id)) {
            return back()->withErrors(['permission' => 'この商品を削除する権限がありません。']);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', '商品を削除しました。');
    }
}
