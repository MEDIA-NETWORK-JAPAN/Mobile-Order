# レイアウトシステム設計書

## 📚 目次

- [1. レイアウト戦略](#1-レイアウト戦略)
  - [1.1 基本方針](#11-基本方針)
  - [1.2 対象デバイス](#12-対象デバイス)
  - [1.3 レイアウト原則](#13-レイアウト原則)
- [2. ブレークポイントシステム](#2-ブレークポイントシステム)
  - [2.1 レスポンシブブレークポイント](#21-レスポンシブブレークポイント)
  - [2.2 コンテナ最大幅](#22-コンテナ最大幅)
  - [2.3 ブレークポイント使用ガイドライン](#23-ブレークポイント使用ガイドライン)
- [3. グリッドシステム](#3-グリッドシステム)
  - [3.1 Flexboxベースグリッド](#31-flexbox-ベースグリッド)
  - [3.2 モバイル特化グリッドパターン](#32-モバイル特化グリッドパターン)
  - [3.3 ガップシステム](#33-ガップシステム)
- [4. コンテナシステム](#4-コンテナシステム)
  - [4.1 基本コンテナ](#41-基本コンテナ)
  - [4.2 セクションコンテナ](#42-セクションコンテナ)
  - [4.3 カードコンテナ](#43-カードコンテナ)
- [5. レイアウトパターン](#5-レイアウトパターン)
  - [5.1 アプリケーションレイアウト](#51-アプリケーションレイアウト)
  - [5.2 リストレイアウト](#52-リストレイアウト)
  - [5.3 フォームレイアウト](#53-フォームレイアウト)
  - [5.4 モーダルレイアウト](#54-モーダルレイアウト)
- [6. ナビゲーションレイアウト](#6-ナビゲーションレイアウト)
  - [6.1 ボトムナビゲーション（モバイル）](#61-ボトムナビゲーションモバイル)
  - [6.2 トップナビゲーション（タブレット以上）](#62-トップナビゲーションタブレット以上)
  - [6.3 ナビゲーションスタイル](#63-ナビゲーションスタイル)
- [7. コンテンツレイアウト](#7-コンテンツレイアウト)
  - [7.1 ヒーローセクション](#71-ヒーローセクション)
  - [7.2 セクション見出し](#72-セクション見出し)
  - [7.3 コンテンツグリッド](#73-コンテンツグリッド)
- [8. モバイル最適化](#8-モバイル最適化)
  - [8.1 タッチターゲット](#81-タッチターゲット)
  - [8.2 スクロール最適化](#82-スクロール最適化)
  - [8.3 セーフエリア対応](#83-セーフエリア対応)
- [9. アクセシビリティ考慮](#9-アクセシビリティ考慮)
  - [9.1 フォーカス管理](#91-フォーカス管理)
  - [9.2 テキスト可読性](#92-テキスト可読性)
  - [9.3 スクリーンリーダー対応](#93-スクリーンリーダー対応)
- [10. パフォーマンス最適化](#10-パフォーマンス最適化)
  - [10.1 レイアウトシフト防止](#101-レイアウトシフト防止)
  - [10.2 Critical CSS](#102-critical-css)

---

## 1. レイアウト戦略

### 1.1 基本方針
- **モバイルファースト**: スマートフォンを最優先としたレスポンシブデザイン
- **コンテンツ重視**: 情報の階層を明確にしたレイアウト構造
- **タッチフレンドリー**: 44px以上のタッチターゲットサイズ
- **シンプル**: 視覚的な複雑さを排除した直感的なレイアウト
- **一貫性**: 全画面で統一されたレイアウトパターン

### 1.2 対象デバイス
- **スマートフォン（320px〜480px）**: プライマリターゲット
- **タブレット（481px〜1024px）**: セカンダリターゲット
- **デスクトップ（1025px以上）**: サポート範囲

### 1.3 レイアウト原則
- **縦スクロール中心**: 横スクロールを避けた縦方向のコンテンツ配置
- **片手操作対応**: 重要な操作は画面下部に集約
- **視覚的階層**: 重要度に応じたコンテンツの配置とサイズ
- **余白の活用**: 適切なホワイトスペースによる視認性向上

## 2. ブレークポイントシステム

### 2.1 レスポンシブブレークポイント
```javascript
// tailwind.config.js
screens: {
  'xs': '320px',    // 小さなスマートフォン
  'sm': '480px',    // 標準スマートフォン
  'md': '769px',    // タブレット
  'lg': '1025px',   // 小さなデスクトップ
  'xl': '1280px',   // 標準デスクトップ
  '2xl': '1536px',  // 大きなデスクトップ
},
```

### 2.2 コンテナ最大幅
```javascript
maxWidth: {
  'xs': '20rem',      // 320px
  'sm': '24rem',      // 384px
  'md': '28rem',      // 448px
  'lg': '32rem',      // 512px
  'xl': '36rem',      // 576px
  '2xl': '42rem',     // 672px
  '3xl': '48rem',     // 768px
  '4xl': '56rem',     // 896px
  '5xl': '64rem',     // 1024px
  '6xl': '72rem',     // 1152px
  '7xl': '80rem',     // 1280px
  'full': '100%',
  'screen-sm': '480px',
  'screen-md': '769px',
  'screen-lg': '1025px',
},
```

### 2.3 ブレークポイント使用ガイドライン
```css
/* モバイルファーストアプローチ */
.responsive-container {
  @apply w-full px-4;                    /* デフォルト（モバイル） */
  @apply sm:px-6;                        /* スマートフォン */
  @apply md:px-8 md:max-w-screen-md;     /* タブレット */
  @apply lg:px-12 lg:max-w-screen-lg;    /* デスクトップ */
  @apply xl:max-w-7xl xl:mx-auto;        /* 大型デスクトップ */
}
```

## 3. グリッドシステム

### 3.1 Flexboxベースグリッド
モバイル最適化のため、CSS GridよりもFlexboxを基本とするグリッドシステム。

```css
/* 基本グリッドクラス */
.grid-container {
  @apply flex flex-wrap;
}

.grid-col-1 { @apply w-full; }
.grid-col-2 { @apply w-1/2; }
.grid-col-3 { @apply w-1/3; }
.grid-col-4 { @apply w-1/4; }
.grid-col-6 { @apply w-1/6; }
.grid-col-12 { @apply w-1/12; }

/* レスポンシブグリッド */
.grid-responsive {
  @apply w-full;           /* モバイル: 1列 */
  @apply sm:w-1/2;         /* スマートフォン: 2列 */
  @apply md:w-1/3;         /* タブレット: 3列 */
  @apply lg:w-1/4;         /* デスクトップ: 4列 */
}
```

### 3.2 モバイル特化グリッドパターン
```blade
{{-- メニューアイテムグリッド --}}
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
    @foreach($menuItems as $item)
        <div class="menu-item-card">
            {{ $item }}
        </div>
    @endforeach
</div>

{{-- カテゴリーグリッド --}}
<div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
    @foreach($categories as $category)
        <div class="category-card">
            {{ $category }}
        </div>
    @endforeach
</div>
```

### 3.3 ガップシステム
```css
/* 間隔の統一 */
.gap-xs { @apply gap-1; }    /* 4px */
.gap-sm { @apply gap-2; }    /* 8px */
.gap-md { @apply gap-4; }    /* 16px */
.gap-lg { @apply gap-6; }    /* 24px */
.gap-xl { @apply gap-8; }    /* 32px */

/* レスポンシブガップ */
.gap-responsive {
  @apply gap-3;              /* モバイル: 12px */
  @apply sm:gap-4;           /* スマートフォン: 16px */
  @apply md:gap-6;           /* タブレット: 24px */
}
```

## 4. コンテナシステム

### 4.1 基本コンテナ
```css
.container-base {
  @apply w-full mx-auto px-4;
  @apply sm:px-6;
  @apply md:px-8;
  @apply lg:px-12;
}

.container-narrow {
  @apply container-base max-w-2xl;
}

.container-wide {
  @apply container-base max-w-7xl;
}

.container-full {
  @apply w-full px-4;
  @apply sm:px-6;
}
```

### 4.2 セクションコンテナ
```css
.section-container {
  @apply py-6 sm:py-8 md:py-12;
}

.section-hero {
  @apply py-12 sm:py-16 md:py-20;
}

.section-compact {
  @apply py-4 sm:py-6;
}
```

### 4.3 カードコンテナ
```css
.card-container {
  @apply bg-white rounded-lg shadow-card;
  @apply p-4 sm:p-6;
  @apply border border-gray-200;
}

.card-compact {
  @apply bg-white rounded-md shadow-sm;
  @apply p-3 sm:p-4;
}
```

## 5. レイアウトパターン

### 5.1 アプリケーションレイアウト

#### ヘッダー + コンテンツ + フッター
```blade
{{-- resources/views/layouts/mobile.blade.php --}}
<div class="min-h-screen flex flex-col">
    {{-- ヘッダー（固定） --}}
    <header class="sticky top-0 z-40 bg-white border-b border-gray-200">
        <div class="container-full">
            <div class="flex items-center justify-between h-14">
                {{ $header }}
            </div>
        </div>
    </header>
    
    {{-- メインコンテンツ --}}
    <main class="flex-1 pb-16">
        <div class="container-full py-4">
            {{ $slot }}
        </div>
    </main>
    
    {{-- フッター（固定ナビゲーション） --}}
    <footer class="fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200">
        {{ $navigation }}
    </footer>
</div>
```

#### サイドバー付きレイアウト（タブレット以上）
```blade
<div class="flex min-h-screen">
    {{-- サイドバー（タブレット以上で表示） --}}
    <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
        <div class="flex-1 flex flex-col min-h-0 bg-gray-50 border-r border-gray-200">
            {{ $sidebar }}
        </div>
    </aside>
    
    {{-- メインコンテンツ --}}
    <div class="md:pl-64 flex flex-col w-0 flex-1">
        <main class="flex-1 relative overflow-y-auto">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</div>
```

### 5.2 リストレイアウト

#### カードリスト
```blade
<div class="space-y-4">
    @foreach($items as $item)
        <div class="card-container">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <img class="h-12 w-12 rounded-lg object-cover" 
                         src="{{ $item->image_url }}" 
                         alt="{{ $item->name }}">
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-heading-sm font-semibold truncate">
                        {{ $item->name }}
                    </h3>
                    <p class="text-body-sm text-gray-600 line-clamp-2">
                        {{ $item->description }}
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <x-mary-button size="sm" class="btn-primary">
                        追加
                    </x-mary-button>
                </div>
            </div>
        </div>
    @endforeach
</div>
```

#### グリッドリスト
```blade
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
    @foreach($items as $item)
        <div class="card-container">
            <div class="aspect-square mb-3">
                <img class="w-full h-full object-cover rounded-lg" 
                     src="{{ $item->image_url }}" 
                     alt="{{ $item->name }}">
            </div>
            <h3 class="text-heading-sm font-semibold line-clamp-2 mb-2">
                {{ $item->name }}
            </h3>
            <p class="text-price font-bold text-primary-600">
                ¥{{ number_format($item->price) }}
            </p>
        </div>
    @endforeach
</div>
```

### 5.3 フォームレイアウト

#### 縦配置フォーム（モバイル最適化）
```blade
<form class="space-y-6">
    <div class="space-y-4">
        <x-mary-input 
            label="店舗名"
            wire:model="name"
            placeholder="店舗名を入力"
            class="w-full"
        />
        
        <x-mary-textarea 
            label="説明"
            wire:model="description"
            placeholder="店舗の説明を入力"
            rows="3"
            class="w-full"
        />
        
        <x-mary-select 
            label="カテゴリー"
            wire:model="category_id"
            :options="$categories"
            placeholder="カテゴリーを選択"
            class="w-full"
        />
    </div>
    
    <div class="flex gap-3">
        <x-mary-button type="submit" class="flex-1 btn-primary">
            保存
        </x-mary-button>
        <x-mary-button type="button" class="btn-secondary">
            キャンセル
        </x-mary-button>
    </div>
</form>
```

#### 横配置フォーム（タブレット以上）
```blade
<form class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-mary-input 
            label="店舗名"
            wire:model="name"
            placeholder="店舗名を入力"
        />
        
        <x-mary-select 
            label="カテゴリー"
            wire:model="category_id"
            :options="$categories"
            placeholder="カテゴリーを選択"
        />
    </div>
    
    <x-mary-textarea 
        label="説明"
        wire:model="description"
        placeholder="店舗の説明を入力"
        rows="3"
    />
    
    <div class="flex gap-3 md:justify-end">
        <x-mary-button type="button" class="btn-secondary">
            キャンセル
        </x-mary-button>
        <x-mary-button type="submit" class="btn-primary">
            保存
        </x-mary-button>
    </div>
</form>
```

### 5.4 モーダルレイアウト

#### フルスクリーンモーダル（モバイル）
```blade
<x-mary-modal wire:model="showModal" class="sm:max-w-lg">
    {{-- ヘッダー --}}
    <x-slot:title class="flex items-center justify-between">
        <h2 class="text-heading-lg font-semibold">{{ $title }}</h2>
        <x-mary-button 
            wire:click="$set('showModal', false)"
            class="btn-ghost btn-sm"
            icon="o-x-mark"
        />
    </x-slot:title>
    
    {{-- コンテンツ --}}
    <div class="py-4">
        {{ $slot }}
    </div>
    
    {{-- アクション --}}
    <x-slot:actions class="flex gap-3">
        <x-mary-button 
            wire:click="$set('showModal', false)"
            class="flex-1 btn-secondary"
        >
            キャンセル
        </x-mary-button>
        <x-mary-button 
            wire:click="save"
            class="flex-1 btn-primary"
        >
            保存
        </x-mary-button>
    </x-slot:actions>
</x-mary-modal>
```

## 6. ナビゲーションレイアウト

### 6.1 ボトムナビゲーション（モバイル）
```blade
<nav class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-30">
    <div class="grid grid-cols-4 py-2">
        <a href="{{ route('menu') }}" 
           class="nav-item {{ request()->routeIs('menu') ? 'active' : '' }}">
            <x-mary-icon name="o-squares-2x2" class="w-6 h-6" />
            <span class="text-xs mt-1">メニュー</span>
        </a>
        
        <button class="nav-item relative" wire:click="$dispatch('toggle-cart')">
            <x-mary-icon name="o-shopping-cart" class="w-6 h-6" />
            <span class="text-xs mt-1">カート</span>
            @if($cartCount > 0)
                <x-mary-badge value="{{ $cartCount }}" class="absolute -top-1 -right-1" />
            @endif
        </button>
        
        <a href="{{ route('orders') }}" 
           class="nav-item {{ request()->routeIs('orders') ? 'active' : '' }}">
            <x-mary-icon name="o-clock" class="w-6 h-6" />
            <span class="text-xs mt-1">注文履歴</span>
        </a>
        
        <a href="{{ route('profile') }}" 
           class="nav-item {{ request()->routeIs('profile') ? 'active' : '' }}">
            <x-mary-icon name="o-user" class="w-6 h-6" />
            <span class="text-xs mt-1">プロフィール</span>
        </a>
    </div>
</nav>
```

### 6.2 トップナビゲーション（タブレット以上）
```blade
<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            {{-- ロゴ --}}
            <div class="flex items-center">
                <h1 class="text-xl font-bold text-primary-600">
                    Mobile Order
                </h1>
            </div>
            
            {{-- メニュー --}}
            <div class="hidden md:flex md:items-center md:space-x-8">
                <a href="{{ route('menu') }}" 
                   class="nav-link {{ request()->routeIs('menu') ? 'active' : '' }}">
                    メニュー
                </a>
                <a href="{{ route('orders') }}" 
                   class="nav-link {{ request()->routeIs('orders') ? 'active' : '' }}">
                    注文履歴
                </a>
                <button class="nav-link relative" wire:click="$dispatch('toggle-cart')">
                    カート
                    @if($cartCount > 0)
                        <x-mary-badge value="{{ $cartCount }}" class="absolute -top-2 -right-2" />
                    @endif
                </button>
            </div>
        </div>
    </div>
</nav>
```

### 6.3 ナビゲーションスタイル
```css
.nav-item {
  @apply flex flex-col items-center justify-center;
  @apply text-gray-500 hover:text-primary-600;
  @apply transition-colors duration-200;
  @apply py-2 px-3 rounded-lg;
  @apply min-h-12 min-w-12; /* タッチターゲット */
}

.nav-item.active {
  @apply text-primary-600 bg-primary-50;
}

.nav-link {
  @apply text-gray-700 hover:text-primary-600;
  @apply transition-colors duration-200;
  @apply font-medium;
}

.nav-link.active {
  @apply text-primary-600 border-b-2 border-primary-600;
}
```

## 7. コンテンツレイアウト

### 7.1 ヒーローセクション
```css
.hero-section {
  @apply py-8 sm:py-12 md:py-16;
  @apply text-center;
}

.hero-title {
  @apply text-display-md sm:text-display-lg;
  @apply font-bold text-gray-900;
  @apply mb-4;
}

.hero-subtitle {
  @apply text-body-lg text-gray-600;
  @apply mb-8;
}
```

### 7.2 セクション見出し
```css
.section-header {
  @apply mb-6 sm:mb-8;
}

.section-title {
  @apply text-heading-lg sm:text-display-sm;
  @apply font-semibold text-gray-900;
  @apply mb-2;
}

.section-description {
  @apply text-body-md text-gray-600;
}
```

### 7.3 コンテンツグリッド
```css
.content-grid {
  @apply grid gap-6 sm:gap-8;
  @apply grid-cols-1 sm:grid-cols-2 lg:grid-cols-3;
}

.content-card {
  @apply bg-white rounded-lg shadow-card;
  @apply p-6 space-y-4;
  @apply hover:shadow-card-hover transition-shadow duration-200;
}
```

## 8. モバイル最適化

### 8.1 タッチターゲット
```css
/* 最小タッチターゲットサイズ */
.touch-target {
  @apply min-h-11 min-w-11; /* 44px以上 */
}

.touch-target-comfortable {
  @apply min-h-12 min-w-12; /* 48px */
}

/* インタラクティブ要素 */
.btn {
  @apply touch-target;
  @apply px-4 py-2;
}

.icon-button {
  @apply touch-target-comfortable;
  @apply flex items-center justify-center;
}
```

### 8.2 スクロール最適化
```css
/* スムーズスクロール */
.smooth-scroll {
  @apply scroll-smooth;
}

/* スクロールスナップ */
.scroll-snap-x {
  @apply overflow-x-auto;
  scroll-snap-type: x mandatory;
}

.scroll-snap-item {
  scroll-snap-align: start;
}

/* スクロールバー非表示（必要な場合） */
.scrollbar-hidden {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hidden::-webkit-scrollbar {
  display: none;
}
```

### 8.3 セーフエリア対応
```css
/* セーフエリア対応（iOS） */
.safe-area-inset {
  padding-top: env(safe-area-inset-top);
  padding-right: env(safe-area-inset-right);
  padding-bottom: env(safe-area-inset-bottom);
  padding-left: env(safe-area-inset-left);
}

.safe-area-inset-top {
  padding-top: env(safe-area-inset-top);
}

.safe-area-inset-bottom {
  padding-bottom: env(safe-area-inset-bottom);
}
```

## 9. アクセシビリティ考慮

### 9.1 フォーカス管理
```css
.focus-visible {
  @apply focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2;
}

.focus-within-highlight {
  @apply focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2;
}
```

### 9.2 テキスト可読性
```css
.text-readable {
  @apply leading-relaxed;
  @apply max-w-prose; /* 最適な行長 */
}

.text-high-contrast {
  @apply text-gray-900; /* コントラスト比21:1 */
}
```

### 9.3 スクリーンリーダー対応
```css
.sr-only {
  @apply absolute w-px h-px p-0 -m-px overflow-hidden;
  @apply whitespace-nowrap border-0;
  clip: rect(0, 0, 0, 0);
}

.not-sr-only {
  @apply static w-auto h-auto p-0 m-0 overflow-visible;
  @apply whitespace-normal;
  clip: auto;
}
```

## 10. パフォーマンス最適化

### 10.1 レイアウトシフト防止
```css
/* 画像のレイアウトシフト防止 */
.aspect-ratio-container {
  @apply relative overflow-hidden;
}

.aspect-square { @apply aspect-w-1 aspect-h-1; }
.aspect-video { @apply aspect-w-16 aspect-h-9; }
.aspect-photo { @apply aspect-w-4 aspect-h-3; }

/* スケルトンローディング */
.skeleton {
  @apply animate-pulse bg-gray-200 rounded;
}
```

### 10.2 Critical CSS
```css
/* Above-the-fold CSS */
.critical {
  @apply container-full;
  @apply bg-white text-gray-900;
  @apply min-h-screen;
}

.critical-header {
  @apply sticky top-0 z-40;
  @apply bg-white border-b border-gray-200;
  @apply h-14;
}

.critical-content {
  @apply py-4 space-y-4;
}
```

---

このレイアウトシステム設計書により、モバイルファーストで一貫したレスポンシブレイアウトを構築できます。TailwindCSSとMary UIを活用し、アクセシブルで高性能なユーザーインターフェースを実現します。