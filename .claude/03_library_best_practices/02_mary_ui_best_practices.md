# Mary UIベストプラクティス文書

## 📚 目次

- [1. Mary UI 概要](#1-mary-ui-概要)
  - [1.1 基本概念](#11-基本概念)
  - [1.2 技術構成](#12-技術構成)
  - [1.3 Mary UIの利点](#13-mary-uiの利点)
- [2. インストールとセットアップ](#2-インストールとセットアップ)
  - [2.1 基本インストール](#21-基本インストール)
  - [2.2 Vite設定](#22-vite設定)
  - [2.3 基本レイアウト設定](#23-基本レイアウト設定)
- [3. コンポーネント別ベストプラクティス](#3-コンポーネント別ベストプラクティス)
  - [3.1 基本コンポーネント](#31-基本コンポーネント)
  - [3.2 レイアウトコンポーネント](#32-レイアウトコンポーネント)
  - [3.3 ナビゲーションコンポーネント](#33-ナビゲーションコンポーネント)
  - [3.4 フィードバックコンポーネント](#34-フィードバックコンポーネント)
  - [3.5 テーブルコンポーネント](#35-テーブルコンポーネント)
- [4. Livewire統合パターン](#4-livewire統合パターン)
  - [4.1 フォーム処理](#41-フォーム処理)
  - [4.2 リアルタイム検索](#42-リアルタイム検索)
  - [4.3 状態管理](#43-状態管理)
- [5. カスタマイズとテーマ](#5-カスタマイズとテーマ)
  - [5.1 カスタムスタイル](#51-カスタムスタイル)
  - [5.2 コンポーネントの拡張](#52-コンポーネントの拡張)
- [6. パフォーマンス最適化](#6-パフォーマンス最適化)
  - [6.1 遅延読み込み](#61-遅延読み込み)
  - [6.2 キャッシュ戦略](#62-キャッシュ戦略)
- [7. アクセシビリティ](#7-アクセシビリティ)
  - [7.1 キーボードナビゲーション](#71-キーボードナビゲーション)
  - [7.2 提供状態表示コンポーネント](#72-提供状態表示コンポーネント)
  - [7.3 意味的なマークアップ](#73-意味的なマークアップ)
- [16. ドラッグ＆ドロップソート実装](#16-ドラッグドロップソート実装)
  - [16.0 SortableJSのセットアップ](#160-sortablejsのセットアップ)
  - [16.1 SortableJSとの統合](#161-sortablejsとの統合)
  - [16.2 Livewireコンポーネント実装](#162-livewireコンポーネント実装)
  - [16.3 商品リストのドラッグ＆ドロップ](#163-商品リストのドラッグドロップ)
  - [16.4 モバイル対応の考慮事項](#164-モバイル対応の考慮事項)
  - [16.5 フォールバック機能（SortableJS無効時）](#165-フォールバック機能sortablejs無効時)
  - [16.6 アクセシビリティ対応](#166-アクセシビリティ対応)
  - [16.7 パフォーマンス最適化](#167-パフォーマンス最適化)

---

## 1. Mary UI 概要

### 1.1 基本概念
Mary UIは、TailwindCSSベースのLivewire専用UIコンポーネントライブラリです。モバイルオーダーシステムにおける高品質なユーザーインターフェースを効率的に構築できます。

### 1.2 技術構成
- **Mary UI v2.4+**: TailwindCSSベースのBladeコンポーネント
- **Livewire 3.6+**: リアクティブコンポーネントとの統合
- **TailwindCSS 4.0**: スタイリングフレームワーク
- **Alpine.js**: クライアントサイドインタラクション（Livewireに含まれる）
- **Heroicons**: SVGアイコンライブラリ

### 1.3 Mary UIの利点
- **Livewire統合**: 完全なLivewire互換性
- **レスポンシブデザイン**: モバイルファースト対応
- **アクセシビリティ**: WCAG 2.1準拠
- **カスタマイズ性**: TailwindCSSによる柔軟なスタイリング
- **豊富なコンポーネント**: 50以上の実用的なコンポーネント

## 2. インストールとセットアップ

### 2.1 基本インストール

#### Composerでのインストール
```bash
# Mary UIインストール
composer require robsontenorio/mary

# 設定ファイル公開
php artisan mary:install

# TailwindCSS設定更新
npm install -D tailwindcss @tailwindcss/forms @tailwindcss/typography
```

#### `tailwind.config.js`設定
```javascript
import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/robsontenorio/mary/src/View/Components/**/*.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#fefbec',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b', // メイン色
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
            },
        },
    },

    plugins: [forms, typography],

    daisyui: {
        themes: [
            {
                light: {
                    primary: '#f59e0b',
                    'primary-content': '#ffffff',
                    secondary: '#6b7280',
                    'secondary-content': '#ffffff',
                    accent: '#3b82f6',
                    'accent-content': '#ffffff',
                    neutral: '#374151',
                    'neutral-content': '#ffffff',
                    'base-100': '#ffffff',
                    'base-200': '#f9fafb',
                    'base-300': '#f3f4f6',
                    'base-content': '#1f2937',
                    info: '#3b82f6',
                    success: '#10b981',
                    warning: '#f59e0b',
                    error: '#ef4444',
                },
            },
        ],
    },
}
```

#### `config/mary.php`設定
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Toast Configuration
    |--------------------------------------------------------------------------
    */
    'toast' => [
        'position' => 'toast-top toast-end',
        'timeout' => 3000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Modal Configuration
    |--------------------------------------------------------------------------
    */
    'modal' => [
        'backdrop_class' => 'backdrop-blur-sm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Drawer Configuration
    |--------------------------------------------------------------------------
    */
    'drawer' => [
        'right' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Theme Configuration
    |--------------------------------------------------------------------------
    */
    'theme' => [
        'default' => 'light',
        'themes' => ['light'],
    ],
];
```

### 2.2 Vite設定
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

### 2.3 基本レイアウト設定
```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Mobile Order System' }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-base-200">
    {{ $slot }}
    
    {{-- Mary UI Toast Container --}}
    <x-mary-toast />
    
    @livewireScripts
</body>
</html>
```

## 3. コンポーネント別ベストプラクティス

### 3.1 基本コンポーネント

#### Button（ボタン）
```blade
{{-- 基本的なボタン --}}
<x-mary-button 
    label="カートに追加"
    wire:click="addToCart"
    class="btn-primary"
    icon="o-plus"
    spinner
/>

{{-- サイズバリエーション --}}
<x-mary-button label="小さなボタン" size="sm" class="btn-primary" />
<x-mary-button label="標準ボタン" size="md" class="btn-primary" />
<x-mary-button label="大きなボタン" size="lg" class="btn-primary" />

{{-- スタイルバリエーション --}}
<x-mary-button label="プライマリ" class="btn-primary" />
<x-mary-button label="セカンダリ" class="btn-secondary" />
<x-mary-button label="アウトライン" class="btn-outline" />
<x-mary-button label="ゴースト" class="btn-ghost" />

{{-- 状態管理 --}}
<x-mary-button 
    label="送信"
    wire:click="submit"
    :disabled="$isSubmitting"
    spinner="submit"
    class="btn-primary"
/>
```

#### Input（入力フィールド）
```blade
{{-- 基本的な入力フィールド --}}
<x-mary-input 
    label="商品名"
    wire:model="name"
    placeholder="商品名を入力してください"
    :error="$errors->first('name')"
    clearable
/>

{{-- 異なる入力タイプ --}}
<x-mary-input 
    label="価格"
    wire:model="price"
    type="number"
    min="0"
    step="0.01"
    prefix="¥"
    :error="$errors->first('price')"
/>

<x-mary-input 
    label="メールアドレス"
    wire:model="email"
    type="email"
    suffix="@example.com"
    :error="$errors->first('email')"
/>

{{-- 検索入力 --}}
<x-mary-input 
    wire:model.live.debounce.300ms="search"
    placeholder="メニューを検索..."
    icon="o-magnifying-glass"
    clearable
    class="mb-4"
/>

{{-- パスワード入力 --}}
<x-mary-password 
    label="パスワード"
    wire:model="password"
    :error="$errors->first('password')"
/>
```

#### Select（選択ボックス）
```blade
{{-- 基本的なSelect --}}
<x-mary-select 
    label="カテゴリー"
    wire:model="category_id"
    :options="$categories"
    placeholder="カテゴリーを選択"
    :error="$errors->first('category_id')"
/>

{{-- 複数選択 --}}
<x-mary-select 
    label="タグ"
    wire:model="tags"
    :options="$allTags"
    placeholder="タグを選択"
    multiple
    searchable
    :error="$errors->first('tags')"
/>

{{-- 検索可能なSelect --}}
<x-mary-select 
    label="店舗"
    wire:model="store_id"
    :options="$stores"
    placeholder="店舗を検索..."
    searchable
    :error="$errors->first('store_id')"
/>
```

#### Textarea（テキストエリア）
```blade
<x-mary-textarea 
    label="説明"
    wire:model="description"
    placeholder="商品の説明を入力してください"
    rows="4"
    :error="$errors->first('description')"
    counter
/>
```

#### Checkbox & Radio
```blade
{{-- チェックボックス --}}
<x-mary-checkbox 
    label="利用規約に同意する"
    wire:model="terms_accepted"
    :error="$errors->first('terms_accepted')"
/>

{{-- ラジオボタン --}}
<x-mary-radio 
    label="支払い方法"
    wire:model="payment_method"
    :options="[
        ['id' => 'cash', 'name' => '現金'],
        ['id' => 'card', 'name' => 'クレジットカード'],
        ['id' => 'qr', 'name' => 'QRコード決済']
    ]"
    :error="$errors->first('payment_method')"
/>
```

### 3.2 レイアウトコンポーネント

#### Card（カード）
```blade
{{-- 基本的なカード --}}
<x-mary-card class="w-full">
    <x-slot:title class="flex items-center gap-2">
        <x-mary-icon name="o-squares-2x2" />
        メニューアイテム
    </x-slot:title>
    
    <div class="space-y-4">
        <p>カードのコンテンツがここに入ります。</p>
        
        <div class="flex items-center justify-between">
            <span class="text-2xl font-bold text-primary">¥1,500</span>
            <x-mary-badge value="人気" class="badge-warning" />
        </div>
    </div>
    
    <x-slot:actions>
        <x-mary-button label="詳細" class="btn-ghost btn-sm" />
        <x-mary-button label="カートに追加" class="btn-primary btn-sm" />
    </x-slot:actions>
</x-mary-card>

{{-- メニューアイテム専用カード --}}
<x-mary-card class="w-full hover:shadow-lg transition-shadow">
    <div class="aspect-square mb-3">
        <img 
            src="{{ $item->image_url }}" 
            alt="{{ $item->name }}"
            class="w-full h-full object-cover rounded-lg"
            loading="lazy"
        >
    </div>
    
    <div class="space-y-2">
        <h3 class="font-semibold text-lg line-clamp-2">{{ $item->name }}</h3>
        <p class="text-gray-600 text-sm line-clamp-2">{{ $item->description }}</p>
        
        <div class="flex items-center justify-between pt-2">
            <span class="text-xl font-bold text-primary">¥{{ number_format($item->price) }}</span>
            <x-mary-button 
                icon="o-plus"
                wire:click="addToCart({{ $item->id }})"
                class="btn-primary btn-sm"
                spinner="addToCart.{{ $item->id }}"
            />
        </div>
    </div>
</x-mary-card>
```

#### Modal（モーダル）
```blade
{{-- 基本的なモーダル --}}
<x-mary-modal wire:model="showModal" title="商品詳細">
    <div class="space-y-4">
        <img 
            src="{{ $selectedItem?->image_url }}" 
            alt="{{ $selectedItem?->name }}"
            class="w-full h-48 object-cover rounded-lg"
        >
        
        <div>
            <h3 class="text-xl font-semibold mb-2">{{ $selectedItem?->name }}</h3>
            <p class="text-gray-600 mb-4">{{ $selectedItem?->description }}</p>
            <p class="text-2xl font-bold text-primary">¥{{ number_format($selectedItem?->price ?? 0) }}</p>
        </div>
        
        {{-- 数量選択 --}}
        <div>
            <x-mary-input 
                label="数量"
                wire:model="quantity"
                type="number"
                min="1"
                max="10"
            />
        </div>
    </div>
    
    <x-slot:actions>
        <x-mary-button label="キャンセル" wire:click="$set('showModal', false)" />
        <x-mary-button 
            label="カートに追加 (¥{{ number_format(($selectedItem?->price ?? 0) * $quantity) }})"
            wire:click="addToCartWithQuantity"
            class="btn-primary"
        />
    </x-slot:actions>
</x-mary-modal>

{{-- フルスクリーンモーダル（モバイル用） --}}
<x-mary-modal 
    wire:model="showFullscreenModal" 
    title="注文確認"
    class="w-full h-full sm:w-11/12 sm:h-5/6 sm:max-w-2xl"
>
    <div class="h-full overflow-y-auto">
        {{-- モーダルコンテンツ --}}
    </div>
</x-mary-modal>
```

#### Drawer（ドロワー）
```blade
{{-- サイドドロワー --}}
<x-mary-drawer 
    wire:model="showCartDrawer" 
    title="カート"
    subtitle="{{ count($cartItems) }}個のアイテム"
    separator
    right
    class="w-full sm:w-96"
>
    @if(empty($cartItems))
        <div class="flex flex-col items-center justify-center h-48">
            <x-mary-icon name="o-shopping-cart" class="w-16 h-16 text-gray-400 mb-4" />
            <p class="text-gray-500">カートは空です</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($cartItems as $index => $item)
                <div class="flex items-center gap-4 p-3 border border-gray-200 rounded-lg">
                    <img 
                        src="{{ $item['image_url'] }}" 
                        alt="{{ $item['name'] }}"
                        class="w-16 h-16 object-cover rounded-lg flex-shrink-0"
                    >
                    
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium truncate">{{ $item['name'] }}</h4>
                        <p class="text-sm text-gray-600">数量: {{ $item['quantity'] }}</p>
                        <p class="text-sm font-semibold text-primary">¥{{ number_format($item['total_price']) }}</p>
                    </div>
                    
                    <x-mary-button 
                        icon="o-trash"
                        wire:click="removeFromCart({{ $index }})"
                        class="btn-ghost btn-sm text-error"
                    />
                </div>
            @endforeach
        </div>
    @endif
    
    <x-slot:actions>
        @if(!empty($cartItems))
            <div class="w-full space-y-4">
                <div class="flex justify-between items-center text-lg font-bold">
                    <span>合計</span>
                    <span class="text-primary">¥{{ number_format($totalPrice) }}</span>
                </div>
                
                <x-mary-button 
                    label="注文を確定する"
                    wire:click="checkout"
                    class="w-full btn-primary btn-lg"
                />
            </div>
        @endif
    </x-slot:actions>
</x-mary-drawer>
```

### 3.3 ナビゲーションコンポーネント

#### Menu（メニュー）
```blade
{{-- ボトムナビゲーション --}}
<x-mary-menu class="fixed bottom-0 left-0 right-0 bg-base-100 border-t">
    <x-mary-menu-item 
        title="メニュー" 
        icon="o-squares-2x2"
        link="{{ route('menu') }}"
        :active="request()->routeIs('menu')"
    />
    
    <x-mary-menu-item 
        title="カート" 
        icon="o-shopping-cart"
        @click="$wire.dispatch('toggle-cart')"
        :badge="$cartCount > 0 ? $cartCount : null"
    />
    
    <x-mary-menu-item 
        title="注文履歴" 
        icon="o-clock"
        link="{{ route('orders') }}"
        :active="request()->routeIs('orders')"
    />
    
    <x-mary-menu-item 
        title="プロフィール" 
        icon="o-user"
        link="{{ route('profile') }}"
        :active="request()->routeIs('profile')"
    />
</x-mary-menu>

{{-- サイドメニュー --}}
<x-mary-menu class="w-56 bg-base-100">
    <x-mary-menu-sub title="メニュー管理">
        <x-mary-menu-item title="商品一覧" icon="o-squares-2x2" link="/admin/items" />
        <x-mary-menu-item title="カテゴリー" icon="o-tag" link="/admin/categories" />
    </x-mary-menu-sub>
    
    <x-mary-menu-sub title="注文管理">
        <x-mary-menu-item title="注文一覧" icon="o-clipboard-document-list" link="/admin/orders" />
        <x-mary-menu-item title="売上分析" icon="o-chart-bar" link="/admin/analytics" />
    </x-mary-menu-sub>
</x-mary-menu>
```

#### Tabs（タブ）
```blade
<x-mary-tabs wire:model="selectedTab" class="mb-6">
    <x-mary-tab name="all" label="すべて" icon="o-squares-2x2">
        {{-- すべてのメニューアイテム --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($allItems as $item)
                <x-menu-item-card :item="$item" />
            @endforeach
        </div>
    </x-mary-tab>
    
    <x-mary-tab name="popular" label="人気" icon="o-fire">
        {{-- 人気のメニューアイテム --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($popularItems as $item)
                <x-menu-item-card :item="$item" />
            @endforeach
        </div>
    </x-mary-tab>
    
    <x-mary-tab name="new" label="新着" icon="o-sparkles">
        {{-- 新着のメニューアイテム --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($newItems as $item)
                <x-menu-item-card :item="$item" />
            @endforeach
        </div>
    </x-mary-tab>
</x-mary-tabs>
```

### 3.4 フィードバックコンポーネント

#### Alert（アラート）
```blade
{{-- 成功メッセージ --}}
@if(session('success'))
    <x-mary-alert 
        type="success" 
        title="成功!"
        description="{{ session('success') }}"
        icon="o-check-circle"
        dismissible
        class="mb-4"
    />
@endif

{{-- エラーメッセージ --}}
@if(session('error'))
    <x-mary-alert 
        type="error" 
        title="エラー"
        description="{{ session('error') }}"
        icon="o-exclamation-triangle"
        dismissible
        class="mb-4"
    />
@endif

{{-- カスタムアラート --}}
<x-mary-alert type="warning" class="mb-4">
    <x-slot:title>
        <div class="flex items-center gap-2">
            <x-mary-icon name="o-exclamation-triangle" />
            在庫に注意
        </div>
    </x-slot:title>
    
    この商品の在庫が残り{{ $item->stock }}個です。お早めにご注文ください。
</x-mary-alert>
```

#### Toast（トースト）
```php
// Livewireコンポーネント内
public function addToCart($itemId)
{
    try {
        // カートに追加するロジック
        $this->cartService->addItem($itemId, 1);
        
        // 成功トースト
        $this->success('カートに追加されました', position: 'toast-top toast-end');
        
    } catch (Exception $e) {
        // エラートースト
        $this->error('カートに追加できませんでした', position: 'toast-top toast-end');
    }
}

public function placeOrder()
{
    try {
        // 注文処理
        $order = $this->orderService->create($this->cartItems);
        
        // 情報トースト
        $this->info("注文番号: {$order->number}", 'ご注文ありがとうございます', position: 'toast-top toast-center', timeout: 5000);
        
    } catch (Exception $e) {
        $this->warning('注文処理中にエラーが発生しました', position: 'toast-top toast-end');
    }
}
```

#### Badge（バッジ）
```blade
{{-- ステータスバッジ --}}
<x-mary-badge 
    value="{{ $order->status_label }}"
    :class="match($order->status) {
        'pending' => 'badge-warning',
        'confirmed' => 'badge-info',
        'preparing' => 'badge-primary',
        'ready' => 'badge-success',
        'completed' => 'badge-success',
        'cancelled' => 'badge-error',
        default => 'badge-ghost'
    }"
/>

{{-- 数量バッジ --}}
<div class="relative">
    <x-mary-icon name="o-shopping-cart" class="w-6 h-6" />
    @if($cartCount > 0)
        <x-mary-badge 
            value="{{ $cartCount }}"
            class="absolute -top-2 -right-2 badge-primary badge-sm"
        />
    @endif
</div>

{{-- 新着バッジ --}}
@if($item->is_new)
    <x-mary-badge value="NEW" class="absolute top-2 right-2 badge-accent" />
@endif
```

### 3.5 テーブルコンポーネント

#### Table（テーブル）
```blade
<x-mary-table :headers="$headers" :rows="$orders" striped>
    @scope('cell_order_number', $order)
        <div class="font-mono text-sm">
            {{ $order->order_number }}
        </div>
    @endscope
    
    @scope('cell_status', $order)
        <x-mary-badge 
            value="{{ $order->status_label }}"
            :class="match($order->status) {
                'pending' => 'badge-warning',
                'confirmed' => 'badge-info',
                'completed' => 'badge-success',
                'cancelled' => 'badge-error',
                default => 'badge-ghost'
            }"
        />
    @endscope
    
    @scope('cell_total', $order)
        <div class="font-semibold text-primary">
            ¥{{ number_format($order->total) }}
        </div>
    @endscope
    
    @scope('cell_actions', $order)
        <div class="flex gap-2">
            <x-mary-button 
                icon="o-eye"
                wire:click="viewOrder({{ $order->id }})"
                class="btn-ghost btn-sm"
                tooltip="詳細を見る"
            />
            
            @if($order->status === 'pending')
                <x-mary-button 
                    icon="o-x-mark"
                    wire:click="cancelOrder({{ $order->id }})"
                    class="btn-ghost btn-sm text-error"
                    tooltip="キャンセル"
                />
            @endif
        </div>
    @endscope
</x-mary-table>
```

## 4. Livewire統合パターン

### 4.1 フォーム処理
```php
// app/Livewire/ProductForm.php
class ProductForm extends Component
{
    public Product $item;
    
    public string $name = '';
    public string $description = '';
    public float $price = 0;
    public int $category_id = 0;
    public bool $is_available = true;
    
    protected array $rules = [
        'name' => 'required|string|max:255',
        'description' => 'required|string|max:1000',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'is_available' => 'boolean',
    ];
    
    public function mount(Product $item = null)
    {
        if ($item->exists) {
            $this->item = $item;
            $this->name = $item->name;
            $this->description = $item->description;
            $this->price = $item->price;
            $this->category_id = $item->category_id;
            $this->is_available = $item->is_available;
        } else {
            $this->item = new Product();
        }
    }
    
    public function save()
    {
        $this->validate();
        
        try {
            $this->item->fill([
                'name' => $this->name,
                'description' => $this->description,
                'price' => $this->price,
                'category_id' => $this->category_id,
                'is_available' => $this->is_available,
            ]);
            
            $this->item->save();
            
            $this->success('商品が保存されました');
            
            return redirect()->route('admin.menu-items.index');
            
        } catch (Exception $e) {
            $this->error('保存中にエラーが発生しました');
        }
    }
    
    public function render()
    {
        return view('livewire.menu-item-form', [
            'categories' => Category::pluck('name', 'id')->toArray(),
        ]);
    }
}
```

```blade
{{-- resources/views/livewire/menu-item-form.blade.php --}}
<div>
    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-mary-input 
                label="商品名"
                wire:model="name"
                placeholder="商品名を入力"
                :error="$errors->first('name')"
                required
            />
            
            <x-mary-select 
                label="カテゴリー"
                wire:model="category_id"
                :options="$categories"
                placeholder="カテゴリーを選択"
                :error="$errors->first('category_id')"
                required
            />
        </div>
        
        <x-mary-input 
            label="価格"
            wire:model="price"
            type="number"
            min="0"
            step="0.01"
            prefix="¥"
            :error="$errors->first('price')"
            required
        />
        
        <x-mary-textarea 
            label="説明"
            wire:model="description"
            placeholder="商品の説明を入力"
            rows="4"
            :error="$errors->first('description')"
            counter
            required
        />
        
        <x-mary-checkbox 
            label="販売可能"
            wire:model="is_available"
            :error="$errors->first('is_available')"
        />
        
        <x-slot:actions>
            <x-mary-button 
                label="キャンセル"
                link="/admin/menu-items"
                class="btn-ghost"
            />
            
            <x-mary-button 
                label="保存"
                type="submit"
                spinner="save"
                class="btn-primary"
            />
        </x-slot:actions>
    </x-mary-form>
</div>
```

### 4.2 リアルタイム検索
```php
// app/Livewire/MenuSearch.php
class MenuSearch extends Component
{
    public string $search = '';
    public int $categoryId = 0;
    public string $sortBy = 'name';
    
    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => 0],
        'sortBy' => ['except' => 'name'],
    ];
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function updatedCategoryId()
    {
        $this->resetPage();
    }
    
    public function clearFilters()
    {
        $this->search = '';
        $this->categoryId = 0;
        $this->sortBy = 'name';
        $this->resetPage();
    }
    
    public function render()
    {
        $items = Product::query()
            ->with(['category'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
            })
            ->when($this->categoryId, function ($query) {
                $query->where('category_id', $this->categoryId);
            })
            ->where('availability_status', 'available')
            ->orderBy($this->sortBy)
            ->paginate(12);
            
        return view('livewire.menu-search', [
            'items' => $items,
            'categories' => Category::pluck('name', 'id')->toArray(),
        ]);
    }
}
```

### 4.3 状態管理
```php
// app/Livewire/Cart.php
class Cart extends Component
{
    public array $items = [];
    public bool $showDrawer = false;
    
    protected $listeners = [
        'add-to-cart' => 'addItem',
        'toggle-cart' => 'toggleDrawer',
    ];
    
    public function mount()
    {
        $this->loadCartFromSession();
    }
    
    public function addItem($itemId, $quantity = 1)
    {
        $menuItem = Product::find($itemId);
        
        if (!$menuItem || $menuItem->availability_status !== 'available') {
            $errorMessage = match($menuItem->availability_status) {
                'sold_out' => 'この商品は売り切れです',
                'not_arrived' => 'この商品は未入荷です',
                'preparing' => 'この商品は準備中です',
                default => 'この商品は現在利用できません'
            };
            $this->error($errorMessage);
            return;
        }
        
        $existingIndex = collect($this->items)->search(function ($item) use ($itemId) {
            return $item['id'] == $itemId;
        });
        
        if ($existingIndex !== false) {
            $this->items[$existingIndex]['quantity'] += $quantity;
            $this->items[$existingIndex]['total_price'] = 
                $this->items[$existingIndex]['price'] * $this->items[$existingIndex]['quantity'];
        } else {
            $this->items[] = [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->price,
                'quantity' => $quantity,
                'total_price' => $menuItem->price * $quantity,
                'image_url' => $menuItem->image_url,
            ];
        }
        
        $this->saveCartToSession();
        $this->dispatch('cart-updated', count: count($this->items));
        $this->success('カートに追加されました');
        $this->showDrawer = true;
    }
    
    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->saveCartToSession();
        $this->dispatch('cart-updated', count: count($this->items));
    }
    
    public function toggleDrawer()
    {
        $this->showDrawer = !$this->showDrawer;
    }
    
    public function getTotalPriceProperty()
    {
        return collect($this->items)->sum('total_price');
    }
    
    private function loadCartFromSession()
    {
        $this->items = session('cart', []);
    }
    
    private function saveCartToSession()
    {
        session(['cart' => $this->items]);
    }
}
```

## 5. カスタマイズとテーマ

### 5.1 カスタムスタイル
```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer components {
    /* ボタンのカスタマイズ */
    .btn-primary {
        @apply bg-primary hover:bg-primary-600 text-white border-primary hover:border-primary-600;
        @apply shadow-sm hover:shadow-md transition-all duration-200;
    }
    
    .btn-secondary {
        @apply bg-gray-100 hover:bg-gray-200 text-gray-800 border-gray-300;
    }
    
    /* カードのカスタマイズ */
    .card-hover {
        @apply transition-all duration-200 hover:shadow-lg hover:-translate-y-1;
    }
    
    /* モバイル最適化 */
    .mobile-card {
        @apply rounded-lg shadow-sm border border-gray-200 bg-white;
        @apply p-4 space-y-3;
    }
    
    .mobile-button {
        @apply min-h-11 px-4 py-2; /* 44px minimum touch target */
        @apply text-sm font-medium;
        @apply transition-all duration-200;
    }
    
    /* アニメーション */
    .fade-in {
        @apply animate-in fade-in-0 duration-200;
    }
    
    .slide-up {
        @apply animate-in slide-in-from-bottom-2 duration-300;
    }
}

/* Mary UIコンポーネントのオーバーライド */
.mary-card {
    @apply shadow-sm hover:shadow-md transition-shadow duration-200;
}

.mary-button {
    @apply transition-all duration-200;
}

.mary-input {
    @apply focus:ring-2 focus:ring-primary focus:border-primary;
}

/* ダークモード対応（将来的に） */
@media (prefers-color-scheme: dark) {
    .dark-mode {
        @apply bg-gray-900 text-white;
    }
}
```

### 5.2 コンポーネントの拡張
```php
// app/View/Components/ProductCard.php
<?php

namespace App\View\Components;

use App\Models\Product;
use Illuminate\View\Component;

class ProductCard extends Component
{
    public function __construct(
        public Product $item,
        public bool $showAddButton = true,
        public string $size = 'default'
    ) {}
    
    public function render()
    {
        return view('components.menu-item-card');
    }
}
```

```blade
{{-- resources/views/components/menu-item-card.blade.php --}}
<x-mary-card @class([
    'menu-item-card',
    'card-hover' => $showAddButton,
    'w-full' => $size === 'default',
    'w-48' => $size === 'small',
    'w-80' => $size === 'large',
    'opacity-60' => !$item->is_available
])>
    {{-- 商品画像 --}}
    <div class="relative aspect-square mb-3">
        <img 
            src="{{ $item->image_url }}" 
            alt="{{ $item->name }}"
            class="w-full h-full object-cover rounded-lg"
            loading="lazy"
        >
        
        {{-- ステータスオーバーレイ --}}
        @if(!$item->is_available)
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center rounded-lg">
                <x-mary-badge value="売り切れ" class="badge-error" />
            </div>
        @endif
        
        {{-- 新着バッジ --}}
        @if($item->is_new)
            <x-mary-badge value="NEW" class="absolute top-2 right-2 badge-accent" />
        @endif
    </div>
    
    {{-- 商品情報 --}}
    <div class="space-y-2">
        <h3 class="font-semibold text-lg line-clamp-2">{{ $item->name }}</h3>
        
        @if($item->description)
            <p class="text-gray-600 text-sm line-clamp-2">{{ $item->description }}</p>
        @endif
        
        <div class="flex items-center justify-between pt-2">
            <span class="text-xl font-bold text-primary">¥{{ number_format($item->price) }}</span>
            
            @if($showAddButton && $item->is_available)
                <x-mary-button 
                    icon="o-plus"
                    wire:click="$dispatch('add-to-cart', { itemId: {{ $item->id }} })"
                    class="btn-primary btn-sm"
                    tooltip="カートに追加"
                />
            @endif
        </div>
    </div>
</x-mary-card>
```

## 6. パフォーマンス最適化

### 6.1 遅延読み込み
```blade
{{-- 画像の遅延読み込み --}}
<img 
    src="{{ $item->image_url }}" 
    alt="{{ $item->name }}"
    loading="lazy"
    decoding="async"
    class="aspect-square object-cover rounded-lg"
>

{{-- Livewireコンポーネントの遅延読み込み --}}
<div wire:loading.remove>
    @foreach($items as $item)
        <x-menu-item-card :item="$item" />
    @endforeach
</div>

<div wire:loading>
    {{-- スケルトンローディング --}}
    @for($i = 0; $i < 8; $i++)
        <div class="animate-pulse">
            <div class="bg-gray-200 aspect-square rounded-lg mb-3"></div>
            <div class="space-y-2">
                <div class="bg-gray-200 h-4 rounded w-3/4"></div>
                <div class="bg-gray-200 h-3 rounded w-1/2"></div>
                <div class="bg-gray-200 h-4 rounded w-1/4"></div>
            </div>
        </div>
    @endfor
</div>
```

### 6.2 キャッシュ戦略
```php
// app/Livewire/ProductGrid.php
public function render()
{
    $cacheKey = "products_{$this->categoryId}_{$this->search}";
    
    $items = Cache::remember($cacheKey, 300, function () {
        return Product::query()
            ->with(['category'])
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->get();
    });
    
    return view('livewire.menu-grid', compact('items'));
}
```

## 7. アクセシビリティ

### 7.1 キーボードナビゲーション
```blade
{{-- フォーカス可能な要素 --}}
<x-mary-button 
    label="カートに追加"
    wire:click="addToCart"
    class="btn-primary focus:ring-2 focus:ring-primary focus:ring-offset-2"
    tabindex="0"
/>

{{-- スクリーンリーダー対応 --}}
<x-mary-button 
    icon="o-plus"
    wire:click="addToCart"
    class="btn-primary"
    aria-label="カートに追加"
    title="カートに追加"
/>
```

### 7.2 提供状態表示コンポーネント
```blade
{{-- 提供状態バッジコンポーネント --}}
@props(['status', 'message' => null, 'expectedTime' => null])

@php
$statusConfig = [
    'available' => [
        'color' => 'success',
        'label' => '販売中',
        'icon' => 'o-check-circle'
    ],
    'sold_out' => [
        'color' => 'error',
        'label' => '売り切れ',
        'icon' => 'o-x-circle'
    ],
    'not_arrived' => [
        'color' => 'warning',
        'label' => '未入荷',
        'icon' => 'o-clock'
    ],
    'preparing' => [
        'color' => 'info',
        'label' => '準備中',
        'icon' => 'o-cog'
    ]
];

$config = $statusConfig[$status] ?? $statusConfig['available'];
@endphp

<div class="flex items-center gap-2">
    <x-mary-badge 
        :value="$config['label']"
        :type="$config['color']"
        :icon="$config['icon']"
    />
    
    @if($message)
        <span class="text-sm text-gray-600">{{ $message }}</span>
    @elseif($expectedTime && in_array($status, ['not_arrived', 'preparing']))
        <span class="text-sm text-gray-600">{{ $expectedTime }}頃予定</span>
    @endif
</div>

{{-- 使用例 --}}
<x-availability-status 
    :status="$item->availability_status"
    :message="$item->availability_message"
    :expected-time="$item->expected_available_time"
/>
```

### 7.3 意味的なマークアップ
```blade
<main role="main" aria-label="メニュー一覧">
    <section aria-labelledby="menu-heading">
        <h1 id="menu-heading" class="text-2xl font-bold mb-6">
            メニュー
        </h1>
        
        <div role="grid" aria-label="メニューアイテム一覧">
            @foreach($items as $item)
                <article role="gridcell" aria-labelledby="item-{{ $item->id }}">
                    <h2 id="item-{{ $item->id }}" class="sr-only">
                        {{ $item->name }}
                    </h2>
                    <x-menu-item-card :item="$item" />
                </article>
            @endforeach
        </div>
    </section>
</main>
```

## 16. ドラッグ＆ドロップソート実装

### 16.0 SortableJSのセットアップ

#### npm/yarnによるインストール（推奨）
```bash
# SortableJSをローカルインストール
npm install sortablejs
# または
yarn add sortablejs
```

#### Vite設定での読み込み
```javascript
// resources/js/app.js
import Sortable from 'sortablejs';
window.Sortable = Sortable;
```

#### 手動配置の場合
```bash
# public/js/vendor/ にダウンロード
mkdir -p public/js/vendor
wget -O public/js/vendor/sortable.min.js https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js
```

### 16.1 SortableJSとの統合
```blade
{{-- resources/views/livewire/admin/category-manager.blade.php --}}
<div class="p-6">
    {{-- カテゴリリスト --}}
    <x-mary-card title="カテゴリ管理">
        <div x-data="sortableCategories()" 
             x-init="initSortable()"
             wire:ignore.self>
            
            <div id="category-list" class="space-y-2">
                @foreach($categories as $category)
                    <div data-id="{{ $category->id }}" 
                         class="sortable-item">
                        <x-mary-list-item :item="$category">
                            <x-slot:avatar>
                                {{-- ドラッグハンドル --}}
                                <x-mary-icon name="o-bars-3" 
                                           class="w-5 h-5 text-gray-400 cursor-move handle" />
                            </x-slot:avatar>
                            
                            <x-slot:value>
                                {{ $category->name }}
                            </x-slot:value>
                            
                            <x-slot:sub-value>
                                <x-mary-badge value="順番: {{ $category->sort_order }}" 
                                            class="badge-ghost badge-sm" />
                            </x-slot:sub-value>
                            
                            <x-slot:actions>
                                <x-mary-button icon="o-pencil" 
                                             wire:click="edit({{ $category->id }})" 
                                             class="btn-ghost btn-sm" />
                            </x-slot:actions>
                        </x-mary-list-item>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- 保存中の表示 --}}
        <x-mary-loading wire:loading wire:target="updateOrder" class="mt-4">
            並び順を保存中...
        </x-mary-loading>
    </x-mary-card>
</div>

@push('scripts')
{{-- SortableJSをローカルに配置（推奨） --}}
<script src="{{ asset('js/vendor/sortable.min.js') }}"></script>
<script>
function sortableCategories() {
    return {
        sortable: null,
        
        initSortable() {
            this.sortable = new Sortable(document.getElementById('category-list'), {
                handle: '.handle',
                animation: 150,
                ghostClass: 'opacity-50',
                chosenClass: 'ring-2 ring-primary',
                dragClass: 'shadow-2xl rotate-2',
                
                // タッチデバイス対応
                forceFallback: true,
                fallbackTolerance: 3,
                touchStartThreshold: 5,
                delay: 100,
                delayOnTouchOnly: true,
                
                // ドラッグ終了時の処理
                onEnd: (evt) => {
                    const orderedIds = Array.from(evt.to.children)
                        .map(el => parseInt(el.dataset.id));
                    
                    // Livewireメソッド呼び出し
                    @this.updateOrder(orderedIds);
                }
            });
        },
        
        destroy() {
            if (this.sortable) {
                this.sortable.destroy();
            }
        }
    }
}
</script>
@endpush
```

### 16.2 Livewireコンポーネント実装
```php
// app/Livewire/Admin/CategoryManager.php
namespace App\Livewire\Admin;

use App\Models\Category;
use Livewire\Component;
use Mary\Traits\Toast;

class CategoryManager extends Component
{
    use Toast;
    
    public $categories;
    
    public function mount()
    {
        $this->loadCategories();
    }
    
    public function loadCategories()
    {
        $this->categories = Category::orderBy('sort_order')
            ->get();
    }
    
    public function updateOrder($orderedIds)
    {
        try {
            \DB::transaction(function () use ($orderedIds) {
                foreach ($orderedIds as $index => $id) {
                    Category::where('id', $id)->update([
                        'sort_order' => $index + 1
                    ]);
                }
            });
            
            $this->success('並び順を更新しました');
            $this->loadCategories();
            
        } catch (\Exception $e) {
            $this->error('並び順の更新に失敗しました');
        }
    }
    
    public function render()
    {
        return view('livewire.admin.category-manager');
    }
}
```

### 16.3 商品リストのドラッグ＆ドロップ
```blade
{{-- 商品一覧でのソート実装 --}}
<x-mary-table :headers="$headers" :rows="$products" striped>
    {{-- ソートハンドル列 --}}
    @scope('cell_sort', $product)
        <div class="sortable-row" data-id="{{ $product->id }}">
            <x-mary-icon name="o-bars-3" 
                       class="w-5 h-5 text-gray-400 cursor-move handle"
                       x-tooltip="ドラッグして並び替え" />
        </div>
    @endscope
    
    {{-- 商品名列 --}}
    @scope('cell_name', $product)
        <div class="flex items-center gap-2">
            @if($product->image_url)
                <x-mary-avatar :image="$product->image_url" class="w-10" />
            @endif
            <div>
                <div class="font-semibold">{{ $product->name }}</div>
                <div class="text-sm text-gray-500">{{ $product->code }}</div>
            </div>
        </div>
    @endscope
    
    {{-- 価格列 --}}
    @scope('cell_price', $product)
        <x-mary-badge value="¥{{ number_format($product->price) }}" 
                    class="badge-outline" />
    @endscope
    
    {{-- アクション列 --}}
    @scope('actions', $product)
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-ellipsis-vertical" 
                             class="btn-ghost btn-sm" />
            </x-slot:trigger>
            
            <x-mary-menu-item title="編集" 
                            icon="o-pencil"
                            wire:click="edit({{ $product->id }})" />
            <x-mary-menu-item title="複製" 
                            icon="o-document-duplicate"
                            wire:click="duplicate({{ $product->id }})" />
            <x-mary-menu-item title="削除" 
                            icon="o-trash"
                            wire:click="delete({{ $product->id }})"
                            wire:confirm="本当に削除しますか？" />
        </x-mary-dropdown>
    @endscope
</x-mary-table>
```

### 16.4 モバイル対応の考慮事項
```javascript
// タブレット・スマートフォンでの操作性向上
initSortable() {
    // デバイス判定
    const isTouchDevice = 'ontouchstart' in window;
    
    this.sortable = new Sortable(document.getElementById('sortable-list'), {
        handle: '.handle',
        animation: 150,
        
        // タッチデバイスの設定
        forceFallback: isTouchDevice,
        fallbackTolerance: isTouchDevice ? 5 : 0,
        touchStartThreshold: isTouchDevice ? 10 : 0,
        
        // 長押しで開始（誤操作防止）
        delay: isTouchDevice ? 200 : 0,
        delayOnTouchOnly: true,
        
        // 視覚的フィードバック
        ghostClass: 'opacity-50',
        chosenClass: isTouchDevice ? 'scale-105 shadow-xl' : 'ring-2 ring-primary',
        dragClass: 'rotate-2 shadow-2xl',
        
        // スクロール設定
        scroll: true,
        scrollSensitivity: 30,
        scrollSpeed: 10,
        
        onEnd: (evt) => {
            const orderedIds = Array.from(evt.to.children)
                .map(el => parseInt(el.dataset.id));
            @this.updateOrder(orderedIds);
        }
    });
}
```

### 16.5 フォールバック機能（SortableJS無効時）

#### 軽量な代替実装
```blade
{{-- SortableJSが読み込めない場合の代替UI --}}
<div x-data="fallbackSort()" class="space-y-2">
    @foreach($categories as $category)
        <div data-id="{{ $category->id }}" class="bg-white p-4 rounded-lg border">
            <div class="flex items-center justify-between">
                <span>{{ $category->name }}</span>
                <div class="flex gap-2">
                    @if(!$loop->first)
                        <x-mary-button icon="o-arrow-up" 
                                     wire:click="moveUp({{ $category->id }})"
                                     class="btn-ghost btn-sm" />
                    @endif
                    @if(!$loop->last)
                        <x-mary-button icon="o-arrow-down" 
                                     wire:click="moveDown({{ $category->id }})"
                                     class="btn-ghost btn-sm" />
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<script>
function fallbackSort() {
    return {
        // SortableJSの動作確認
        init() {
            if (typeof window.Sortable === 'undefined') {
                console.warn('SortableJS not loaded, using fallback buttons');
                // フォールバック用のボタンを表示
                document.querySelectorAll('.sortable-fallback').forEach(el => {
                    el.style.display = 'block';
                });
            }
        }
    }
}
</script>
```

#### Livewireコンポーネント側の実装
```php
// フォールバック用のメソッド追加
public function moveUp($itemId)
{
    $item = $this->model::find($itemId);
    $prevItem = $this->model::where('sort_order', '<', $item->sort_order)
                           ->orderBy('sort_order', 'desc')
                           ->first();
    
    if ($prevItem) {
        \DB::transaction(function () use ($item, $prevItem) {
            $tempOrder = $item->sort_order;
            $item->update(['sort_order' => $prevItem->sort_order]);
            $prevItem->update(['sort_order' => $tempOrder]);
        });
        
        $this->success('並び順を更新しました');
        $this->loadItems();
    }
}

public function moveDown($itemId)
{
    $item = $this->model::find($itemId);
    $nextItem = $this->model::where('sort_order', '>', $item->sort_order)
                           ->orderBy('sort_order', 'asc')
                           ->first();
    
    if ($nextItem) {
        \DB::transaction(function () use ($item, $nextItem) {
            $tempOrder = $item->sort_order;
            $item->update(['sort_order' => $nextItem->sort_order]);
            $nextItem->update(['sort_order' => $tempOrder]);
        });
        
        $this->success('並び順を更新しました');
        $this->loadItems();
    }
}
```

### 16.6 アクセシビリティ対応
```blade
{{-- キーボード操作も可能にする --}}
<div class="sortable-item"
     data-id="{{ $item->id }}"
     tabindex="0"
     role="listitem"
     aria-label="{{ $item->name }} - 並び順{{ $item->sort_order }}"
     @keydown.arrow-up.prevent="moveUp({{ $item->id }})"
     @keydown.arrow-down.prevent="moveDown({{ $item->id }})"
     @keydown.space.prevent="toggleSelection({{ $item->id }})">
    
    <div class="flex items-center gap-3">
        {{-- ビジュアルインジケーター --}}
        <button type="button"
                class="handle"
                aria-label="ドラッグして並び替え"
                title="ドラッグして並び替え（↑↓キーでも移動可能）">
            <x-mary-icon name="o-bars-3" class="w-5 h-5" />
        </button>
        
        {{-- コンテンツ --}}
        <div class="flex-1">{{ $item->name }}</div>
        
        {{-- 順番表示 --}}
        <span class="text-sm text-gray-500" aria-label="現在の順番">
            #{{ $item->sort_order }}
        </span>
        
        {{-- フォールバック用ボタン（非表示） --}}
        <div class="sortable-fallback hidden flex gap-1">
            <x-mary-button icon="o-arrow-up" 
                         wire:click="moveUp({{ $item->id }})"
                         class="btn-ghost btn-xs"
                         :disabled="$loop->first" />
            <x-mary-button icon="o-arrow-down" 
                         wire:click="moveDown({{ $item->id }})"
                         class="btn-ghost btn-xs"
                         :disabled="$loop->last" />
        </div>
    </div>
</div>
```

### 16.7 パフォーマンス最適化
```javascript
// 大量データ対応
initSortable() {
    // アイテム数をチェック
    const itemCount = document.querySelectorAll('.sortable-item').length;
    
    this.sortable = new Sortable(document.getElementById('sortable-list'), {
        handle: '.handle',
        animation: itemCount > 50 ? 0 : 150, // 大量データ時はアニメーション無効
        
        // 仮想スクロール対応（大量データ時）
        scroll: true,
        scrollSensitivity: 30,
        scrollSpeed: itemCount > 100 ? 20 : 10,
        
        // Debounce処理
        onEnd: this.debounce((evt) => {
            const orderedIds = Array.from(evt.to.children)
                .map(el => parseInt(el.dataset.id));
            @this.updateOrder(orderedIds);
        }, 300)
    });
},

// Debounce utility
debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
```

---

このMary UIベストプラクティス文書により、効率的で保守性の高いUIコンポーネントを構築できます。Livewireとの密接な統合により、リアクティブで高性能なユーザーインターフェースを実現し、モバイルファーストのアプローチでアクセシブルなアプリケーションを開発できます。ドラッグ＆ドロップによる直感的な操作で、管理画面の使いやすさが大幅に向上します。