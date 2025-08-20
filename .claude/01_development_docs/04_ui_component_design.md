# UIコンポーネント設計書

## 📚 目次

- [1. 設計方針](#1-設計方針)
  - [1.1 技術スタック](#11-技術スタック)
  - [1.2 コンポーネント設計原則](#12-コンポーネント設計原則)
- [2. コンポーネント階層](#2-コンポーネント階層)
  - [2.1 構造](#21-構造)
  - [2.2 分類](#22-分類)
- [3. デザイントークン](#3-デザイントークン)
  - [3.1 カラーパレット](#31-カラーパレット)
  - [3.2 タイポグラフィ](#32-タイポグラフィ)
  - [3.3 スペーシング](#33-スペーシング)
- [4. 主要コンポーネント仕様](#4-主要コンポーネント仕様)
  - [4.1 ProductGrid（商品グリッド）](#41-productgrid商品グリッド)
  - [4.2 CartComponent（カートコンポーネント）](#42-cartcomponentカートコンポーネント)
  - [4.3 Mary UI 基本コンポーネント活用](#43-mary-ui-基本コンポーネント活用)
  - [4.4 モーダル/ドロワー](#44-モーダルドロワー)
- [5. レスポンシブデザイン](#5-レスポンシブデザイン)
  - [5.1 ブレークポイント](#51-ブレークポイント)
  - [5.2 モバイル最適化](#52-モバイル最適化)
- [6. アクセシビリティ](#6-アクセシビリティ)
  - [6.1 基本要件](#61-基本要件)
  - [6.2 実装例](#62-実装例)
- [7. パフォーマンス最適化](#7-パフォーマンス最適化)
  - [7.1 画像最適化](#71-画像最適化)
  - [7.2 Livewire最適化](#72-livewire最適化)
- [8. アニメーション](#8-アニメーション)
  - [8.1 トランジション](#81-トランジション)
  - [8.2 ローディング表示](#82-ローディング表示)
- [11. ドラッグ＆ドロップソート機能](#11-ドラッグドロップソート機能)
  - [11.1 対象テーブル](#111-対象テーブル)
  - [11.2 実装方法（Livewire + Alpine.js）](#112-実装方法livewire--alpinejs)
  - [11.3 Mary UIを使った実装](#113-mary-uiを使った実装)
  - [11.4 タッチデバイス対応](#114-タッチデバイス対応)
  - [11.5 UX改善ポイント](#115-ux改善ポイント)

---

## 1. 設計方針

### 1.1 技術スタック
- **Livewire 3.6+**: リアクティブコンポーネント
- **Alpine.js**: Livewireにバンドル、クライアントサイドインタラクション
- **Mary UI v2.4+**: TailwindCSSベースのUIライブラリ
- **TailwindCSS 4.0**: ユーティリティファーストCSS

### 1.2 コンポーネント設計原則
- **Livewireファースト**: 可能な限りLivewireコンポーネントで実装
- **サーバーサイドレンダリング**: SEO対応とパフォーマンス
- **リアクティブUI**: ポーリングとLivewireのリアルタイム更新
- **モバイルファースト**: レスポンシブデザイン

## 2. コンポーネント階層

### 2.1 構造
```
app/
├── Livewire/           # Livewireコンポーネント
│   ├── Customer/       # お客様向け
│   │   ├── ProductGrid.php
│   │   ├── CartComponent.php
│   │   └── OrderHistory.php
│   ├── Admin/          # 管理者向け
│   │   ├── MenuManager.php
│   │   └── OrderDashboard.php
│   └── Shared/         # 共通
│       ├── Alert.php
│       └── LoadingSpinner.php
└── View/Components/    # Bladeコンポーネント
    ├── Layouts/
    │   ├── AppLayout.php
    │   └── GuestLayout.php
    └── UI/
        ├── Button.php
        └── Card.php
```

### 2.2 分類
- **Livewireコンポーネント**: 状態管理とサーバー通信が必要な要素
- **Bladeコンポーネント**: 静的または単純な表示要素
- **Mary UIコンポーネント**: 基本的なUI要素（ボタン、フォーム等）

## 3. デザイントークン

### 3.1 カラーパレット
```css
/* tailwind.config.js */
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#fefbec',   // 極淡いアンバー（背景用）
          100: '#fef3c7',  // 淡いアンバー（ホバー）
          200: '#fde68a',  // 明るいアンバー
          300: '#fcd34d',  // 中間のアンバー
          400: '#fbbf24',  // 少し深いアンバー
          500: '#f59e0b',  /* メインカラー（アンバー） */
          600: '#d97706',  // 深いアンバー（ボタン等）
          700: '#b45309',  // より深いアンバー
          800: '#92400e',  // 暗いアンバー
          900: '#78350f',  // 最も暗いアンバー
        },
        secondary: {
          500: '#6366f1',  /* インディゴ */
        },
        success: {
          500: '#10b981',  /* エメラルド */
        },
        warning: {
          500: '#f59e0b',  /* アンバー */
        },
        error: {
          500: '#ef4444',  /* レッド */
        },
      },
    },
  },
}
```

### 3.2 タイポグラフィ
```css
/* フォントサイズ */
.text-xs   { font-size: 0.75rem; }   /* 12px */
.text-sm   { font-size: 0.875rem; }  /* 14px */
.text-base { font-size: 1rem; }      /* 16px (基本) */
.text-lg   { font-size: 1.125rem; }  /* 18px */
.text-xl   { font-size: 1.25rem; }   /* 20px */
.text-2xl  { font-size: 1.5rem; }    /* 24px */
.text-3xl  { font-size: 1.875rem; }  /* 30px */

/* フォントウェイト */
.font-normal  { font-weight: 400; }
.font-medium  { font-weight: 500; }
.font-semibold { font-weight: 600; }
.font-bold    { font-weight: 700; }
```

### 3.3 スペーシング
```css
/* 基本単位: 4px */
.space-1  { margin/padding: 0.25rem; }  /* 4px */
.space-2  { margin/padding: 0.5rem; }   /* 8px */
.space-3  { margin/padding: 0.75rem; }  /* 12px */
.space-4  { margin/padding: 1rem; }     /* 16px */
.space-6  { margin/padding: 1.5rem; }   /* 24px */
.space-8  { margin/padding: 2rem; }     /* 32px */
.space-12 { margin/padding: 3rem; }     /* 48px */
```

## 4. 主要コンポーネント仕様

### 4.1 ProductGrid（商品グリッド）
```php
// app/Livewire/Customer/ProductGrid.php
<?php
namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Product;

class ProductGrid extends Component
{
    public $orderMode = 'image'; // 'image' or 'number'
    public $selectedCategoryId = null;
    public $productCode = '';
    public $allProducts = [];
    public $filteredProducts = [];
    
    // 60秒ごとに提供状態を更新
    protected $listeners = ['refreshComponent' => '$refresh'];
    
    public function mount()
    {
        $this->loadAllProducts();
        $this->filterProducts();
    }
    
    public function loadAllProducts()
    {
        $this->allProducts = Product::with('categories')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }
    
    public function filterProducts()
    {
        $this->filteredProducts = collect($this->allProducts)
            ->when($this->selectedCategoryId, fn($items) => 
                $items->where('category_id', $this->selectedCategoryId)
            )
            ->sortBy('sort_order')
            ->values()
            ->toArray();
    }
    
    public function selectCategory($categoryId)
    {
        $this->selectedCategoryId = $categoryId;
        $this->filterProducts();
    }
    
    public function searchByCode()
    {
        if ($this->orderMode === 'number' && $this->productCode) {
            $product = collect($this->allProducts)
                ->firstWhere('code', $this->productCode);
                
            if ($product) {
                $this->dispatch('product-found', $product);
            } else {
                $this->dispatch('product-not-found');
            }
        }
    }
    
    public function render()
    {
        return view('livewire.customer.product-grid', [
            'products' => $this->filteredProducts,
            'categories' => collect($this->allProducts)
                ->groupBy('category_name')
                ->keys()
                ->toArray()
        ]);
    }
}
```

```blade
{{-- resources/views/livewire/customer/product-grid.blade.php --}}
<div wire:poll.60s class="space-y-4">
    {{-- 注文方式タブ --}}
    <div class="flex bg-gray-100 rounded-lg p-1">
        <button 
            wire:click="$set('orderMode', 'image')"
            class="flex-1 py-2 px-4 rounded-md text-sm font-medium {{ $orderMode === 'image' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500' }}"
        >
            🖼️ 画像で選ぶ
        </button>
        <button 
            wire:click="$set('orderMode', 'number')"
            class="flex-1 py-2 px-4 rounded-md text-sm font-medium {{ $orderMode === 'number' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500' }}"
        >
            🔢 番号で注文
        </button>
    </div>

    @if($orderMode === 'image')
        {{-- 画像選択モード --}}
        <div class="space-y-4">
            {{-- カテゴリフィルター --}}
            <div class="flex gap-2 overflow-x-auto pb-2">
                <button 
                    wire:click="selectCategory(null)"
                    class="px-4 py-2 rounded-full whitespace-nowrap {{ !$selectedCategoryId ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-700' }}"
                >
                    すべて
                </button>
                @foreach($categories as $category)
                    <button 
                        wire:click="selectCategory({{ $category['id'] }})"
                        class="px-4 py-2 rounded-full whitespace-nowrap {{ $selectedCategoryId === $category['id'] ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-700' }}"
                    >
                        {{ $category['name'] }}
                    </button>
                @endforeach
            </div>

            {{-- 商品グリッド --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($products as $product)
                    <x-mary-card shadow class="cursor-pointer hover:shadow-lg transition-shadow">
                        {{-- 商品番号表示 --}}
                        <div class="absolute top-2 left-2 bg-black bg-opacity-60 text-white text-xs px-2 py-1 rounded">
                            {{ $product['code'] }}
                        </div>
                        
                        {{-- 画像 --}}
                        <div class="aspect-square overflow-hidden rounded-lg mb-3 relative">
                            <img 
                                src="{{ $product['image_url'] }}" 
                                alt="{{ $product['name'] }}"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                            @if($product['availability_status'] !== 'available')
                                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">
                                        @switch($product['availability_status'])
                                            @case('sold_out') 売り切れ @break
                                            @case('not_arrived') 未入荷 @break
                                            @case('preparing') 準備中 @break
                                        @endswitch
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        {{-- 商品情報 --}}
                        <h3 class="font-semibold text-lg mb-1">{{ $product['name'] }}</h3>
                        <p class="text-sm text-gray-600 mb-2">{{ $product['description'] }}</p>
                        
                        {{-- 価格とボタン --}}
                        <div class="flex justify-between items-center mt-4">
                            <span class="text-xl font-bold text-primary-600">¥{{ number_format($product['tax_in_price']) }}</span>
                            @if($product['availability_status'] === 'available')
                                <x-mary-button 
                                    wire:click="addToCart({{ $product['id'] }})"
                                    size="sm"
                                    class="btn-primary"
                                >
                                    カートに追加
                                </x-mary-button>
                            @endif
                        </div>
                    </x-mary-card>
                @endforeach
            </div>
        </div>
    @else
        {{-- 番号入力モード --}}
        <div class="space-y-4">
            <div class="text-center">
                <h3 class="text-lg font-semibold mb-4">商品番号を入力してください</h3>
                <div class="flex gap-2 justify-center">
                    <input 
                        wire:model="productCode"
                        wire:keydown.enter="searchByCode"
                        type="text" 
                        placeholder="例：001"
                        class="px-4 py-2 border rounded-lg text-center text-lg font-mono"
                        maxlength="10"
                    >
                    <x-mary-button wire:click="searchByCode" class="btn-primary">
                        検索
                    </x-mary-button>
                </div>
            </div>
        </div>
    @endif
</div>
```

### 4.2 CartComponent（カートコンポーネント）
```php
// app/Livewire/Customer/CartComponent.php
class CartComponent extends Component
{
    public $items = [];
    public $isOpen = false;
    
    protected $listeners = [
        'itemAddedToCart' => 'addItem',
        'toggleCart' => 'toggle'
    ];
    
    public function addItem($productId, $quantity = 1, $options = [])
    {
        // カートに商品追加ロジック（オプション付き）
        $this->emit('cartUpdated', count($this->items));
    }
    
    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }
    
    public function updateQuantity($index, $quantity)
    {
        if ($quantity > 0 && $quantity <= 10) {
            $this->items[$index]['quantity'] = $quantity;
        }
    }
}
```

### 4.3 Mary UI 基本コンポーネント活用

#### ボタン
```blade
{{-- Primary ボタン --}}
<x-mary-button class="btn-primary">
    注文する
</x-mary-button>

{{-- Secondary ボタン --}}
<x-mary-button variant="secondary">
    キャンセル
</x-mary-button>

{{-- アイコン付きボタン --}}
<x-mary-button icon="o-shopping-cart" class="btn-primary">
    カートを見る
</x-mary-button>
```

#### フォーム要素
```blade
{{-- 入力フィールド --}}
<x-mary-input 
    label="お名前" 
    wire:model="name" 
    placeholder="山田太郎"
    error="{{ $errors->first('name') }}"
/>

{{-- セレクトボックス --}}
<x-mary-select 
    label="人数" 
    wire:model="customerCount"
    :options="[1 => '1名', 2 => '2名', 3 => '3名', 4 => '4名以上']"
/>

{{-- ラジオボタン --}}
<x-mary-radio 
    label="お支払い方法" 
    wire:model="paymentMethod"
    :options="['cash' => '現金', 'card' => 'クレジットカード']"
/>
```

#### アラート
```blade
{{-- 成功メッセージ --}}
<x-mary-alert type="success" dismissible>
    注文が正常に送信されました！
</x-mary-alert>

{{-- エラーメッセージ --}}
<x-mary-alert type="error">
    ご注文できない商品が含まれています。
</x-mary-alert>

{{-- 情報メッセージ --}}
<x-mary-alert type="info" icon="o-information-circle">
    ラストオーダーは21:30です。
</x-mary-alert>
```

### 4.4 モーダル/ドロワー
```blade
{{-- モーダル（商品詳細） --}}
<x-mary-modal wire:model="showItemDetail" title="商品詳細">
    <div class="space-y-4">
        <img src="{{ $selectedItem->image_url }}" class="w-full rounded-lg">
        <h3 class="text-xl font-bold">{{ $selectedItem->name }}</h3>
        <p>{{ $selectedItem->description }}</p>
        <p class="text-lg font-semibold">¥{{ number_format($selectedItem->tax_in_price) }}</p>
        
        {{-- オプション選択 --}}
        @foreach($selectedItem->options as $option)
            <div class="border-t pt-4">
                <label class="font-medium">
                    {{ $option->title }}
                    @if($option->required)
                        <span class="text-red-500">*必須</span>
                    @endif
                </label>
                <p class="text-sm text-gray-600 mb-3">{{ $option->description }}</p>
                
                @if($option->selection_type === 'single')
                    {{-- 単一選択（ラジオボタン） --}}
                    @foreach($option->optionProducts as $choice)
                        <label class="flex items-center justify-between p-2 border rounded mb-2">
                            <div class="flex items-center space-x-3">
                                <input type="radio" 
                                       name="option_{{ $option->id }}" 
                                       value="{{ $choice->id }}"
                                       wire:model="selectedOptions.{{ $option->id }}"
                                       @if($choice->pivot->default && !isset($selectedOptions[$option->id])) checked @endif>
                                <div>
                                    <span class="font-medium">{{ $choice->name }}</span>
                                    @if($choice->availability_status !== 'available')
                                        <span class="text-sm text-red-500 block">
                                            @switch($choice->availability_status)
                                                @case('sold_out') 売り切れ @break
                                                @case('not_arrived') 未入荷 @break
                                                @case('preparing') 準備中 @break
                                            @endswitch
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <span class="font-medium">
                                ¥{{ number_format($choice->tax_in_price) }}
                            </span>
                        </label>
                    @endforeach
                @else
                    {{-- 複数選択（数量指定） --}}
                    @foreach($option->optionProducts as $choice)
                        <div class="flex items-center justify-between p-2 border rounded mb-2">
                            <div>
                                <span class="font-medium">{{ $choice->name }}</span>
                                <span class="text-sm text-gray-600 block">
                                    ¥{{ number_format($choice->tax_in_price) }}
                                </span>
                                @if($choice->availability_status !== 'available')
                                    <span class="text-sm text-red-500">
                                        @switch($choice->availability_status)
                                            @case('sold_out') 売り切れ @break
                                            @case('not_arrived') 未入荷 @break
                                            @case('preparing') 準備中 @break
                                        @endswitch
                                    </span>
                                @endif
                            </div>
                            @if($choice->availability_status === 'available')
                                <div class="flex items-center space-x-2">
                                    <button type="button" 
                                            wire:click="decrementOption({{ $option->id }}, {{ $choice->id }})"
                                            class="w-8 h-8 rounded-full border flex items-center justify-center">
                                        -
                                    </button>
                                    <span class="w-8 text-center">
                                        {{ $selectedOptions[$option->id][$choice->id] ?? 0 }}
                                    </span>
                                    <button type="button"
                                            wire:click="incrementOption({{ $option->id }}, {{ $choice->id }})"
                                            class="w-8 h-8 rounded-full border flex items-center justify-center">
                                        +
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach
        
        {{-- 合計価格表示 --}}
        <div class="border-t pt-4">
            <div class="flex justify-between items-center text-lg font-semibold">
                <span>合計</span>
                <span>¥{{ number_format($calculatedTotalPrice) }}</span>
            </div>
        </div>
    </div>
    
    <x-slot:actions>
        <x-mary-button wire:click="closeModal" variant="ghost">
            キャンセル
        </x-mary-button>
        <x-mary-button wire:click="addToCart" class="btn-primary">
            カートに追加
        </x-mary-button>
    </x-slot:actions>
</x-mary-modal>

{{-- ドロワー（カート） --}}
<x-mary-drawer wire:model="cartOpen" right class="w-96">
    <x-mary-header title="カート" separator />
    
    <div class="space-y-4">
        @foreach($cartItems as $item)
            <x-mary-list-item :item="$item">
                <x-slot:actions>
                    <x-mary-button 
                        icon="o-trash" 
                        wire:click="removeItem({{ $loop->index }})"
                        size="sm"
                        variant="ghost"
                    />
                </x-slot:actions>
            </x-mary-list-item>
        @endforeach
    </div>
    
    <x-slot:actions>
        <x-mary-button wire:click="checkout" class="w-full btn-primary">
            注文を確定する
        </x-mary-button>
    </x-slot:actions>
</x-mary-drawer>
```

## 5. レスポンシブデザイン

### 5.1 ブレークポイント
```css
/* TailwindCSS デフォルト */
sm: 640px   /* タブレット */
md: 768px   /* 小型PC */
lg: 1024px  /* PC */
xl: 1280px  /* 大型PC */
2xl: 1536px /* 超大型PC */
```

### 5.2 モバイル最適化
```blade
{{-- グリッドレイアウト --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    {{-- コンテンツ --}}
</div>

{{-- テキストサイズ --}}
<h1 class="text-2xl sm:text-3xl lg:text-4xl">見出し</h1>

{{-- パディング --}}
<div class="p-4 sm:p-6 lg:p-8">
    {{-- コンテンツ --}}
</div>

{{-- 表示/非表示 --}}
<div class="hidden sm:block">PCのみ表示</div>
<div class="block sm:hidden">モバイルのみ表示</div>
```

## 6. アクセシビリティ

### 6.1 基本要件
- **セマンティックHTML**: 適切なHTML要素の使用
- **ARIA属性**: 必要に応じて追加
- **キーボード操作**: Tab順序、Enterキー対応
- **スクリーンリーダー**: 適切なラベル付け

### 6.2 実装例
```blade
{{-- フォームラベル --}}
<label for="email" class="sr-only">メールアドレス</label>
<input 
    id="email" 
    type="email" 
    aria-label="メールアドレス"
    aria-required="true"
    wire:model="email"
>

{{-- ボタン --}}
<button 
    type="submit"
    aria-label="注文を確定する"
    {{ $isProcessing ? 'aria-busy="true"' : '' }}
>
    @if($isProcessing)
        <x-mary-loading /> 処理中...
    @else
        注文を確定
    @endif
</button>

{{-- エラーメッセージ --}}
<div role="alert" aria-live="polite">
    <p class="text-red-600">{{ $error }}</p>
</div>
```

## 7. パフォーマンス最適化

### 7.1 画像最適化
```blade
{{-- 遅延読み込み --}}
<img 
    src="{{ $item->image_url }}" 
    loading="lazy"
    decoding="async"
    alt="{{ $item->name }}"
>

{{-- レスポンシブ画像 --}}
<picture>
    <source media="(max-width: 640px)" srcset="{{ $item->image_url_mobile }}">
    <source media="(min-width: 641px)" srcset="{{ $item->image_url_desktop }}">
    <img src="{{ $item->image_url }}" alt="{{ $item->name }}">
</picture>
```

### 7.2 Livewire最適化
```php
// 遅延読み込み
public function loadItems()
{
    if ($this->readyToLoad) {
        $this->items = Product::active()->get();
    }
}

// デバウンス検索
<input wire:model.debounce.500ms="search" type="search">

// ローディング状態
<div wire:loading>
    <x-mary-loading />
</div>
```

### 7.3 SPA Navigation実装パターン

#### 基本的なwire:navigate使用
```blade
{{-- 内部リンクにwire:navigateを追加 --}}
<nav>
    <a href="/menu" wire:navigate>メニュー</a>
    <a href="/cart" wire:navigate>カート</a>
    <a href="/order-history" wire:navigate>注文履歴</a>
</nav>

{{-- ボタンでの画面遷移 --}}
<button onclick="window.location.href='/menu'" wire:navigate>
    メニューに戻る
</button>
```

#### Prefetch戦略
```blade
{{-- デフォルト: クリック時のプリフェッチ --}}
<a href="/product/{{ $product->id }}" wire:navigate>
    {{ $product->name }}
</a>

{{-- ホバー時のプリフェッチ（60ms後） --}}
<a href="/product/{{ $product->id }}" wire:navigate.hover>
    {{ $product->name }}
</a>
```

#### プログラマティックナビゲーション
```php
// Livewireコンポーネント内でのリダイレクト
public function proceedToCart()
{
    // SPAライクな遷移を維持
    $this->redirect('/cart', navigate: true);
}

public function completeOrder()
{
    // 注文完了後の遷移
    $this->redirect('/order-complete', navigate: true);
}
```

#### ブラウザバック無効化との統合
```blade
{{-- 注文フロー内でのナビゲーション --}}
<div x-data="{ 
    init() {
        // ブラウザバック無効化
        history.pushState(null, null, location.href);
        window.addEventListener('popstate', (e) => {
            history.pushState(null, null, location.href);
        });
    }
}">
    <a href="/checkout" wire:navigate>注文確定へ進む</a>
</div>
```

#### 永続要素の実装（@persist）
```blade
{{-- カート情報を画面遷移間で永続化 --}}
@persist('cart-summary')
<div class="cart-summary">
    <span>{{ $cartItemCount }}点</span>
    <span>¥{{ number_format($cartTotal) }}</span>
</div>
@endpersist

{{-- オーディオプレーヤーの永続化 --}}
@persist('bgm-player')
<audio id="bgm" autoplay loop>
    <source src="/audio/bgm.mp3" type="audio/mpeg">
</audio>
@endpersist
```

#### アセットトラッキング
```blade
{{-- Vite使用時は自動でdata-navigate-trackが付与される --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- 手動でのトラッキング設定 --}}
<script src="/js/custom.js?v={{ config('app.version') }}" data-navigate-track></script>
```

## 8. アニメーション

### 8.1 トランジション
```blade
{{-- Alpine.js トランジション --}}
<div 
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 transform scale-90"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-90"
>
    コンテンツ
</div>

{{-- Livewire トランジション --}}
<div wire:transition.opacity.duration.200ms>
    動的コンテンツ
</div>
```

### 8.2 ローディング表示
```blade
{{-- スケルトンローディング --}}
<div wire:loading.remove>
    {{-- 実際のコンテンツ --}}
</div>

<div wire:loading>
    <div class="animate-pulse">
        <div class="h-48 bg-gray-200 rounded"></div>
        <div class="h-4 bg-gray-200 rounded mt-4"></div>
        <div class="h-4 bg-gray-200 rounded mt-2 w-3/4"></div>
    </div>
</div>
```

## 11. ドラッグ＆ドロップソート機能

### 11.1 対象テーブル
sort_orderカラムを持つ以下のテーブルで実装：
- **categories**: カテゴリ一覧
- **category_product**: カテゴリ内商品
- **product_to_options**: 商品オプション
- **option_detail**: オプション選択肢
- **images**: 商品画像

### 11.2 実装方法（Livewire + Alpine.js）

#### Livewireコンポーネント
```php
// app/Livewire/Admin/SortableList.php
<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class SortableList extends Component
{
    public $items = [];
    public $model;
    public $parentId = null;
    
    public function mount($model, $parentId = null)
    {
        $this->model = $model;
        $this->parentId = $parentId;
        $this->loadItems();
    }
    
    public function loadItems()
    {
        $query = $this->model::query();
        
        if ($this->parentId) {
            $query->where('category_id', $this->parentId);
        }
        
        $this->items = $query->orderBy('sort_order')->get()->toArray();
    }
    
    public function updateOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            $this->model::where('id', $id)->update([
                'sort_order' => $index + 1
            ]);
        }
        
        $this->dispatch('sorted', message: '並び順を更新しました');
        $this->loadItems();
    }
    
    public function render()
    {
        return view('livewire.admin.sortable-list');
    }
}
```

#### Bladeビュー
```blade
{{-- resources/views/livewire/admin/sortable-list.blade.php --}}
<div x-data="sortableList()" 
     x-init="initSortable()"
     wire:ignore.self>
    
    <div id="sortable-items" class="space-y-2">
        @foreach($items as $item)
            <div data-id="{{ $item['id'] }}" 
                 class="sortable-item bg-white p-4 rounded-lg border border-gray-200 cursor-move hover:shadow-md transition-shadow">
                
                <div class="flex items-center">
                    {{-- ドラッグハンドル --}}
                    <svg class="w-6 h-6 text-gray-400 mr-3 handle" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    
                    {{-- アイテム内容 --}}
                    <div class="flex-1">
                        <span class="font-medium">{{ $item['name'] ?? $item['title'] }}</span>
                        @if(isset($item['code']))
                            <span class="text-sm text-gray-500 ml-2">({{ $item['code'] }})</span>
                        @endif
                    </div>
                    
                    {{-- ソート順表示 --}}
                    <span class="text-sm text-gray-400">
                        #{{ $item['sort_order'] }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- 保存中インジケーター --}}
    <div wire:loading wire:target="updateOrder" 
         class="fixed bottom-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg">
        並び順を保存中...
    </div>
</div>

@push('scripts')
<script>
function sortableList() {
    return {
        sortable: null,
        
        initSortable() {
            // SortableJSを使用
            this.sortable = new Sortable(document.getElementById('sortable-items'), {
                handle: '.handle',
                animation: 150,
                ghostClass: 'opacity-50',
                dragClass: 'shadow-2xl',
                onEnd: (evt) => {
                    // 新しい順序を取得
                    const orderedIds = Array.from(evt.to.children).map(el => 
                        parseInt(el.dataset.id)
                    );
                    
                    // Livewireに送信
                    @this.updateOrder(orderedIds);
                }
            });
        }
    }
}
</script>
@endpush
```

### 11.3 Mary UIを使った実装
```blade
{{-- Mary UIのテーブルコンポーネントと統合 --}}
<x-mary-table :headers="$headers" :rows="$items" sortable>
    @scope('cell_sort', $item)
        <div class="sortable-row flex items-center" data-id="{{ $item->id }}">
            <x-mary-icon name="o-bars-3" class="w-5 h-5 text-gray-400 cursor-move handle" />
        </div>
    @endscope
    
    @scope('cell_name', $item)
        <div class="flex items-center gap-2">
            <span>{{ $item->name }}</span>
            <x-mary-badge :value="$item->sort_order" class="badge-sm" />
        </div>
    @endscope
</x-mary-table>
```

### 11.4 タッチデバイス対応
```javascript
// モバイルデバイスでのタッチ操作対応
initSortable() {
    this.sortable = new Sortable(document.getElementById('sortable-items'), {
        handle: '.handle',
        animation: 150,
        forceFallback: true, // タッチデバイス対応
        fallbackTolerance: 3,
        touchStartThreshold: 5,
        
        // 長押しで移動開始（誤操作防止）
        delay: 100,
        delayOnTouchOnly: true,
        
        onEnd: (evt) => {
            const orderedIds = Array.from(evt.to.children).map(el => 
                parseInt(el.dataset.id)
            );
            @this.updateOrder(orderedIds);
        }
    });
}
```

### 11.5 UX改善ポイント
1. **視覚的フィードバック**
   - ドラッグ中は影を追加
   - ドロップ可能エリアをハイライト
   - 保存中は明確に表示

2. **操作性**
   - ドラッグハンドルで誤操作防止
   - キーボード操作も可能（アクセシビリティ）
   - 変更の自動保存

3. **パフォーマンス**
   - Debounce処理で連続操作を最適化
   - wire:ignore.selfで再レンダリング防止

---

このUIコンポーネント設計書に従うことで、Livewire + Mary UI + TailwindCSSを活用した一貫性のあるユーザーインターフェースを構築できます。
