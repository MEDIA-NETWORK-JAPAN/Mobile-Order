# コンポーネント設計書

## 1. コンポーネント戦略

### 1.1 基本方針
- **Mary UIファースト**: 可能な限りMary UIの既存コンポーネントを活用
- **カスタマイズ最小化**: 必要最小限のカスタマイズで統一感を保持
- **モバイル最適化**: スマートフォンでの使用を最優先
- **Livewire統合**: Livewireコンポーネントとの密接な連携
- **アクセシビリティ**: WCAG 2.1 AA準拠

### 1.2 技術構成
- **Mary UI v2.4+**: ベースUIライブラリ
- **TailwindCSS 4.0**: スタイリングフレームワーク
- **Livewire 3.6+**: リアクティブコンポーネント
- **Alpine.js**: クライアントサイドインタラクション
- **Heroicons**: アイコンライブラリ（Mary UIに含まれる）

## 2. コンポーネント分類

### 2.1 階層構造
```
コンポーネント階層
├── Atoms（原子）
│   ├── Button
│   ├── Input
│   ├── Icon
│   ├── Badge
│   └── Avatar
├── Molecules（分子）
│   ├── FormField
│   ├── Card
│   ├── ListItem
│   ├── Alert
│   └── SearchBox
├── Organisms（有機体）
│   ├── Header
│   ├── MenuGrid
│   ├── CartDrawer
│   ├── OrderSummary
│   └── Navigation
└── Templates（テンプレート）
    ├── PageLayout
    ├── ModalLayout
    └── FormLayout
```

### 2.2 Livewireコンポーネント連携
- **データバインディング**: `wire:model`を活用した双方向データバインディング
- **イベントハンドリング**: `wire:click`、`wire:submit`等のイベント処理
- **リアルタイム更新**: `wire:poll`を使用した定期更新
- **バリデーション**: Livewireのリアルタイムバリデーション連携

## 3. Atoms（基本コンポーネント）

### 3.1 Button コンポーネント

#### 基本実装
```blade
{{-- Mary UI Buttonの基本使用 --}}
<x-mary-button 
    class="btn-primary"
    size="md"
    :disabled="$disabled"
    wire:click="handleClick"
>
    {{ $label }}
</x-mary-button>
```

#### カスタムバリエーション
```css
/* resources/css/components/buttons.css */
@layer components {
  .btn-primary {
    @apply bg-primary-600 hover:bg-primary-700 text-white;
    @apply focus:ring-2 focus:ring-primary-500 focus:ring-offset-2;
    @apply transition-colors duration-200;
    @apply min-h-11 px-4;  /* タッチターゲット44px以上 */
  }
  
  .btn-secondary {
    @apply bg-gray-100 hover:bg-gray-200 text-gray-900;
    @apply border border-gray-300;
  }
  
  .btn-outline {
    @apply bg-transparent hover:bg-primary-50 text-primary-600;
    @apply border border-primary-600 hover:border-primary-700;
  }
  
  .btn-ghost {
    @apply bg-transparent hover:bg-gray-100 text-gray-700;
  }
  
  .btn-danger {
    @apply bg-error-600 hover:bg-error-700 text-white;
  }
}
```

#### サイズバリエーション
```css
.btn-xs { @apply text-xs px-2 py-1 min-h-8; }
.btn-sm { @apply text-sm px-3 py-1.5 min-h-9; }
.btn-md { @apply text-base px-4 py-2 min-h-11; }  /* デフォルト */
.btn-lg { @apply text-lg px-6 py-3 min-h-12; }
.btn-xl { @apply text-xl px-8 py-4 min-h-14; }
```

### 3.2 Input コンポーネント

#### 基本実装
```blade
{{-- Mary UI Inputの活用 --}}
<x-mary-input 
    label="商品名"
    wire:model="name"
    placeholder="商品名を入力してください"
    :error="$errors->first('name')"
    class="input-standard"
    required
/>
```

#### カスタムスタイル
```css
.input-standard {
  @apply border-gray-300 focus:border-primary-500 focus:ring-primary-500;
  @apply rounded-input min-h-11;
  @apply text-base;
}

.input-error {
  @apply border-error-500 focus:border-error-500 focus:ring-error-500;
}

.input-success {
  @apply border-success-500 focus:border-success-500 focus:ring-success-500;
}
```

### 3.3 Badge コンポーネント

#### 実装例
```blade
{{-- ステータスバッジ --}}
<x-mary-badge 
    :value="$status"
    :class="match($status) {
        'pending' => 'badge-warning',
        'confirmed' => 'badge-info', 
        'completed' => 'badge-success',
        'cancelled' => 'badge-error',
        default => 'badge-default'
    }"
/>
```

#### バッジスタイル
```css
.badge-default { @apply bg-gray-100 text-gray-800; }
.badge-primary { @apply bg-primary-100 text-primary-800; }
.badge-success { @apply bg-success-100 text-success-800; }
.badge-warning { @apply bg-warning-100 text-warning-800; }
.badge-error { @apply bg-error-100 text-error-800; }
.badge-info { @apply bg-info-100 text-info-800; }
```

## 4. Molecules（複合コンポーネント）

### 4.1 Card コンポーネント

#### 基本カード
```blade
{{-- Mary UI Cardの活用 --}}
<x-mary-card class="mobile-card" shadow>
    <x-slot:title class="card-title">
        {{ $title }}
    </x-slot:title>
    
    <div class="card-content">
        {{ $slot }}
    </div>
    
    @if($hasActions)
        <x-slot:actions class="card-actions">
            {{ $actions }}
        </x-slot:actions>
    @endif
</x-mary-card>
```

#### カードスタイル
```css
.mobile-card {
  @apply rounded-card bg-white border border-gray-200;
  @apply shadow-card hover:shadow-card-hover;
  @apply transition-shadow duration-200;
}

.card-title {
  @apply text-heading-md text-gray-900 font-semibold;
}

.card-content {
  @apply p-4 space-y-3;
}

.card-actions {
  @apply p-4 pt-0 flex gap-2 justify-end;
}
```

### 4.2 MenuItemCard コンポーネント

#### 専用コンポーネント
```php
// app/View/Components/MenuItemCard.php
class MenuItemCard extends Component
{
    public function __construct(
        public MenuItem $item,
        public bool $showAddButton = true
    ) {}
    
    public function render()
    {
        return view('components.menu-item-card');
    }
}
```

```blade
{{-- resources/views/components/menu-item-card.blade.php --}}
<x-mary-card class="menu-item-card" :class="!$item->is_available ? 'opacity-60' : ''">
    <div class="relative">
        <img 
            src="{{ $item->image_url }}" 
            alt="{{ $item->name }}"
            class="aspect-square object-cover rounded-lg w-full"
            loading="lazy"
        >
        
        @if(!$item->is_available)
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center rounded-lg">
                <span class="text-white font-semibold text-sm">売り切れ</span>
            </div>
        @endif
    </div>
    
    <div class="p-3 space-y-2">
        <h3 class="text-heading-sm font-semibold text-gray-900 line-clamp-2">
            {{ $item->name }}
        </h3>
        
        @if($item->description)
            <p class="text-body-sm text-gray-600 line-clamp-2">
                {{ $item->description }}
            </p>
        @endif
        
        <div class="flex items-center justify-between">
            <span class="text-price text-primary-600 font-bold">
                ¥{{ number_format($item->price) }}
            </span>
            
            @if($showAddButton && $item->is_available)
                <x-mary-button 
                    wire:click="addToCart({{ $item->id }})"
                    class="btn-primary btn-sm"
                    icon="o-plus"
                >
                    追加
                </x-mary-button>
            @endif
        </div>
    </div>
</x-mary-card>
```

### 4.3 Alert コンポーネント

#### フラッシュメッセージ
```blade
{{-- Mary UI Alertの活用 --}}
@if (session()->has('success'))
    <x-mary-alert 
        type="success" 
        class="mobile-alert"
        dismissible
        timeout="3000"
        icon="o-check-circle"
    >
        {{ session('success') }}
    </x-mary-alert>
@endif

@if (session()->has('error'))
    <x-mary-alert 
        type="error" 
        class="mobile-alert"
        dismissible
        icon="o-exclamation-triangle"
    >
        {{ session('error') }}
    </x-mary-alert>
@endif
```

#### アラートスタイル
```css
.mobile-alert {
  @apply rounded-md p-4 mb-4;
  @apply border-l-4;
}

.mobile-alert[type="success"] {
  @apply bg-success-50 border-success-400 text-success-800;
}

.mobile-alert[type="error"] {
  @apply bg-error-50 border-error-400 text-error-800;
}

.mobile-alert[type="warning"] {
  @apply bg-warning-50 border-warning-400 text-warning-800;
}

.mobile-alert[type="info"] {
  @apply bg-info-50 border-info-400 text-info-800;
}
```

## 5. Organisms（複合体コンポーネント）

### 5.1 MenuGrid Livewireコンポーネント

#### Livewireコンポーネント
```php
// app/Livewire/Customer/MenuGrid.php
class MenuGrid extends Component
{
    public $categoryId = null;
    public $searchTerm = '';
    public $sortBy = 'sort_order';
    
    protected $listeners = [
        'categoryChanged' => 'setCategoryId',
        'refreshMenu' => '$refresh'
    ];
    
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
        $this->resetPage();
    }
    
    public function addToCart($menuItemId)
    {
        try {
            $menuItem = MenuItem::findOrFail($menuItemId);
            
            if (!$menuItem->is_available) {
                throw new BusinessException('BIZ-STK-001');
            }
            
            $this->dispatch('item-added-to-cart', menuItemId: $menuItemId);
            
            session()->flash('success', 'カートに追加されました');
            
        } catch (BusinessException $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    public function render()
    {
        $items = MenuItem::query()
            ->with(['category'])
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->searchTerm, fn($q) => $q->where('name', 'like', "%{$this->searchTerm}%"))
            ->where('is_active', true)
            ->orderBy($this->sortBy)
            ->get();
            
        return view('livewire.customer.menu-grid', compact('items'));
    }
}
```

#### Bladeテンプレート
```blade
{{-- resources/views/livewire/customer/menu-grid.blade.php --}}
<div>
    {{-- 検索・フィルター --}}
    <div class="mb-6 space-y-4">
        <x-mary-input 
            wire:model.live.debounce.300ms="searchTerm"
            placeholder="メニューを検索..."
            icon="o-magnifying-glass"
            clearable
        />
    </div>
    
    {{-- メニューグリッド --}}
    <div 
        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4"
        wire:poll.10s="$refresh"
    >
        @forelse($items as $item)
            <x-menu-item-card :item="$item" />
        @empty
            <div class="col-span-full text-center py-8">
                <x-mary-icon name="o-face-frown" class="w-12 h-12 text-gray-400 mx-auto mb-2" />
                <p class="text-gray-500">メニューが見つかりませんでした</p>
            </div>
        @endforelse
    </div>
    
    {{-- ローディング状態 --}}
    <div wire:loading class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
        <x-mary-loading class="w-8 h-8" />
    </div>
</div>
```

### 5.2 CartDrawer コンポーネント

#### Livewireコンポーネント
```php
// app/Livewire/Customer/CartDrawer.php
class CartDrawer extends Component
{
    public $isOpen = false;
    public $items = [];
    
    protected $listeners = [
        'item-added-to-cart' => 'addItem',
        'toggle-cart' => 'toggle'
    ];
    
    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
    }
    
    public function addItem($menuItemId)
    {
        // カートロジック
        $this->refreshCart();
        $this->isOpen = true;
    }
    
    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }
    
    public function checkout()
    {
        // チェックアウト処理
    }
    
    private function refreshCart()
    {
        // カート内容の取得
    }
}
```

#### Drawer実装
```blade
{{-- resources/views/livewire/customer/cart-drawer.blade.php --}}
<div>
    {{-- カートボタン --}}
    <x-mary-button 
        wire:click="toggle"
        class="btn-primary relative"
        icon="o-shopping-cart"
    >
        カート
        @if(count($items) > 0)
            <x-mary-badge 
                value="{{ count($items) }}"
                class="absolute -top-2 -right-2 bg-error-500 text-white"
            />
        @endif
    </x-mary-button>
    
    {{-- Drawer --}}
    <x-mary-drawer 
        wire:model="isOpen"
        right
        class="w-full sm:w-96"
        title="カート"
        separator
    >
        @if(empty($items))
            <div class="text-center py-8">
                <x-mary-icon name="o-shopping-cart" class="w-12 h-12 text-gray-400 mx-auto mb-2" />
                <p class="text-gray-500">カートは空です</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($items as $index => $item)
                    <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg">
                        <img 
                            src="{{ $item['image_url'] }}" 
                            alt="{{ $item['name'] }}"
                            class="w-12 h-12 object-cover rounded"
                        >
                        
                        <div class="flex-1">
                            <h4 class="font-semibold text-sm">{{ $item['name'] }}</h4>
                            <p class="text-xs text-gray-600">数量: {{ $item['quantity'] }}</p>
                            <p class="text-sm font-bold text-primary-600">
                                ¥{{ number_format($item['total_price']) }}
                            </p>
                        </div>
                        
                        <x-mary-button 
                            wire:click="removeItem({{ $index }})"
                            class="btn-ghost btn-sm"
                            icon="o-trash"
                        />
                    </div>
                @endforeach
            </div>
        @endif
        
        @if(!empty($items))
            <x-slot:actions>
                <div class="w-full space-y-3">
                    <div class="flex justify-between items-center text-lg font-bold">
                        <span>合計</span>
                        <span class="text-primary-600">
                            ¥{{ number_format(collect($items)->sum('total_price')) }}
                        </span>
                    </div>
                    
                    <x-mary-button 
                        wire:click="checkout"
                        class="w-full btn-primary btn-lg"
                    >
                        注文を確定する
                    </x-mary-button>
                </div>
            </x-slot:actions>
        @endif
    </x-mary-drawer>
</div>
```

## 6. Templates（レイアウト）

### 6.1 モバイルレイアウト

#### 基本レイアウト
```blade
{{-- resources/views/layouts/mobile.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? 'Mobile Order System' }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900">
    {{-- ヘッダー --}}
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
        <div class="px-4 py-3">
            {{ $header ?? '' }}
        </div>
    </header>
    
    {{-- メインコンテンツ --}}
    <main class="min-h-screen pb-20">
        <div class="px-4 py-6">
            {{ $slot }}
        </div>
    </main>
    
    {{-- フッター（ナビゲーション） --}}
    <footer class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-30">
        {{ $footer ?? '' }}
    </footer>
    
    {{-- グローバルアラート --}}
    <div class="fixed top-16 left-4 right-4 z-50">
        <livewire:shared.flash-messages />
    </div>
    
    @livewireScripts
</body>
</html>
```

### 6.2 ナビゲーションコンポーネント

#### ボトムナビゲーション
```blade
{{-- resources/views/components/bottom-navigation.blade.php --}}
<nav class="flex items-center justify-around py-2">
    <a href="{{ route('customer.menu') }}" 
       class="nav-item {{ request()->routeIs('customer.menu') ? 'active' : '' }}">
        <x-mary-icon name="o-squares-2x2" class="w-6 h-6" />
        <span class="text-xs mt-1">メニュー</span>
    </a>
    
    <button 
        wire:click="$dispatch('toggle-cart')"
        class="nav-item relative"
    >
        <x-mary-icon name="o-shopping-cart" class="w-6 h-6" />
        <span class="text-xs mt-1">カート</span>
        <livewire:customer.cart-counter />
    </button>
    
    <a href="{{ route('customer.orders') }}" 
       class="nav-item {{ request()->routeIs('customer.orders') ? 'active' : '' }}">
        <x-mary-icon name="o-clock" class="w-6 h-6" />
        <span class="text-xs mt-1">注文履歴</span>
    </a>
</nav>
```

#### ナビゲーションスタイル
```css
.nav-item {
  @apply flex flex-col items-center justify-center;
  @apply text-gray-500 hover:text-primary-600;
  @apply transition-colors duration-200;
  @apply py-2 px-3 rounded-lg;
  @apply min-h-12; /* タッチターゲット */
}

.nav-item.active {
  @apply text-primary-600 bg-primary-50;
}

.nav-item:focus {
  @apply outline-none ring-2 ring-primary-500 ring-offset-2;
}
```

## 7. 状態管理とイベント

### 7.1 Livewireイベント

#### グローバルイベント
```php
// 共通イベント定義
class Events
{
    const CART_UPDATED = 'cart-updated';
    const ITEM_ADDED = 'item-added-to-cart';
    const ORDER_PLACED = 'order-placed';
    const MENU_REFRESHED = 'menu-refreshed';
}
```

#### イベント使用例
```php
// イベント発火
$this->dispatch(Events::CART_UPDATED, count: $this->getCartItemCount());

// イベント受信
protected $listeners = [
    Events::CART_UPDATED => 'handleCartUpdate',
];

public function handleCartUpdate($count)
{
    $this->cartCount = $count;
}
```

### 7.2 Alpine.jsとの連携

#### 軽量インタラクション
```blade
{{-- アニメーション付きアラート --}}
<div 
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-90"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-90"
    x-init="setTimeout(() => show = false, 3000)"
    class="alert"
>
    {{ $message }}
</div>
```

## 8. パフォーマンス最適化

### 8.1 遅延読み込み

#### 画像の遅延読み込み
```blade
<img 
    src="{{ $item->image_url }}" 
    alt="{{ $item->name }}"
    loading="lazy"
    decoding="async"
    class="aspect-square object-cover"
>
```

#### Livewireの遅延読み込み
```php
public $readyToLoad = false;

public function loadContent()
{
    $this->readyToLoad = true;
}

public function render()
{
    return view('livewire.component', [
        'items' => $this->readyToLoad ? $this->getItems() : collect(),
    ]);
}
```

### 8.2 キャッシュ戦略

#### コンポーネントキャッシュ
```php
// Mary UIコンポーネントでのキャッシュ
public function render()
{
    $cacheKey = "menu-items-{$this->categoryId}";
    
    $items = Cache::remember($cacheKey, 300, function () {
        return MenuItem::with(['category'])
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->get();
    });
    
    return view('livewire.menu-grid', compact('items'));
}
```

---

このコンポーネント設計書により、Mary UIを最大限活用しながら、モバイルファーストで一貫したUIコンポーネントシステムを構築できます。Livewireとの密接な統合により、リアクティブで高性能なユーザーインターフェースを実現します。