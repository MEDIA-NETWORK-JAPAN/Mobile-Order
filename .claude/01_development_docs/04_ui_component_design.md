# UIコンポーネント設計書

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
          50: '#fef3c7',
          100: '#fde68a',
          200: '#fcd34d',
          300: '#fbbf24',
          400: '#f59e0b',
          500: '#d97706',  /* メインカラー（アンバー） */
          600: '#b45309',
          700: '#92400e',
          800: '#78350f',
          900: '#451a03',
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
    public $categoryId = null;
    public $searchTerm = '';
    public $sortBy = 'sort_order';
    
    // 10秒ごとに提供状態を更新
    protected $listeners = ['refreshComponent' => '$refresh'];
    
    public function mount($categoryId = null)
    {
        $this->categoryId = $categoryId;
    }
    
    public function render()
    {
        $items = Product::query()
            ->when($this->categoryId, function($q) {
                $q->whereHas('categories', function($query) {
                    $query->where('categories.id', $this->categoryId);
                });
            })
            ->when($this->searchTerm, fn($q) => $q->search($this->searchTerm))
            ->orderBy($this->sortBy)
            ->get();
            
        return view('livewire.customer.product-grid', compact('items'));
    }
}
```

```blade
{{-- resources/views/livewire/customer/product-grid.blade.php --}}
<div wire:poll.10s class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach($items as $item)
        <x-mary-card shadow class="cursor-pointer hover:shadow-lg transition-shadow">
            {{-- 画像 --}}
            <div class="aspect-square overflow-hidden rounded-lg mb-3">
                <img 
                    src="{{ $item->image_url }}" 
                    alt="{{ $item->name }}"
                    class="w-full h-full object-cover"
                    loading="lazy"
                >
                @if($item->availability_status !== 'available')
                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                        <span class="text-white font-bold text-lg">
                            @switch($item->availability_status)
                                @case('sold_out')
                                    売り切れ
                                    @break
                                @case('not_arrived')
                                    未入荷
                                    @break
                                @case('preparing')
                                    準備中
                                    @break
                            @endswitch
                        </span>
                    </div>
                @endif
            </div>
            
            {{-- 商品情報 --}}
            <h3 class="font-semibold text-lg mb-1">{{ $item->name }}</h3>
            <p class="text-sm text-gray-600 mb-2">{{ $item->description }}</p>
            
            {{-- 価格とボタン --}}
            <div class="flex justify-between items-center mt-4">
                <span class="text-xl font-bold text-primary-600">¥{{ number_format($item->price) }}</span>
                @if($item->availability_status === 'available')
                    <x-mary-button 
                        wire:click="addToCart({{ $item->id }})"
                        size="sm"
                        class="btn-primary"
                    >
                        カートに追加
                    </x-mary-button>
                @else
                    <div class="text-sm text-gray-500">
                        @if($item->availability_message)
                            {{ $item->availability_message }}
                        @elseif($item->expected_available_time)
                            {{ $item->expected_available_time }}ごろ提供予定
                        @endif
                    </div>
                @endif
            </div>
        </x-mary-card>
    @endforeach
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

---

このUIコンポーネント設計書に従うことで、Livewire + Mary UI + TailwindCSSを活用した一貫性のあるユーザーインターフェースを構築できます。