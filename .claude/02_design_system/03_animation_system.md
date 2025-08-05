# アニメーションシステム設計書

## 1. アニメーション方針

### 1.1 基本方針
- **機能第一**: アニメーションは機能的な目的のみに使用
- **最小限の使用**: 装飾的アニメーションは使用しない
- **パフォーマンス重視**: 軽量で高速なアニメーション
- **アクセシビリティ**: モーションを減らしたいユーザーへの対応
- **モバイル最適化**: バッテリーとCPU使用量を考慮

### 1.2 使用ケース
以下の機能的アニメーションのみを許可します：

1. **フィードバックアニメーション**
   - フラッシュメッセージの表示・非表示
   - ローディングスピナー
   - ボタンのホバー・フォーカス状態

2. **状態変化アニメーション**
   - モーダル・ドロワーの開閉
   - ドロップダウンメニューの展開・折りたたみ
   - タブの切り替え

3. **プログレス表示**
   - 注文進行状況のアニメーション
   - アップロード進行状況

4. **マイクロインタラクション**
   - カードの軽いホバーエフェクト
   - フォームフィールドのフォーカス状態

## 2. アニメーション時間とイージング

### 2.1 基本タイミング
```javascript
// tailwind.config.js
transitionDuration: {
  'fast': '150ms',      // 高速アニメーション（ホバー等）
  'normal': '200ms',    // 標準アニメーション（デフォルト）
  'slow': '300ms',      // ゆっくりアニメーション（モーダル等）
},

transitionTimingFunction: {
  'ease-smooth': 'cubic-bezier(0.4, 0, 0.2, 1)',     // 滑らかな移行
  'ease-bounce': 'cubic-bezier(0.68, -0.55, 0.265, 1.55)', // 軽いバウンス
  'ease-swift': 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',    // 素早い動き
},
```

### 2.2 アニメーションタイプ別設定
```css
/* ホバーエフェクト（高速） */
.hover-lift {
  @apply transition-all duration-fast ease-smooth;
  @apply hover:shadow-card-hover hover:-translate-y-0.5;
}

/* フェードインアウト（標準） */
.fade-transition {
  @apply transition-opacity duration-normal ease-smooth;
}

/* スライドアニメーション（ゆっくり） */
.slide-transition {
  @apply transition-transform duration-slow ease-smooth;
}
```

## 3. Alpine.jsトランジション

### 3.1 フラッシュメッセージアニメーション
```blade
{{-- フラッシュメッセージのフェードイン・アウト --}}
<div 
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    x-init="setTimeout(() => show = false, 3000)"
    class="alert"
>
    {{ $message }}
</div>
```

### 3.2 モーダルアニメーション
```blade
{{-- モーダルのフェードイン・スケール --}}
<div 
    x-show="isOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-90"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-90"
    class="modal-content"
>
    {{ $slot }}
</div>

{{-- モーダル背景 --}}
<div 
    x-show="isOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="modal-backdrop"
></div>
```

### 3.3 ドロップダウンアニメーション
```blade
{{-- ドロップダウンメニュー --}}
<div 
    x-data="{ open: false }"
    class="relative"
>
    <button @click="open = !open" class="dropdown-trigger">
        メニュー
    </button>
    
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        @click.away="open = false"
        class="dropdown-menu"
    >
        {{ $menuItems }}
    </div>
</div>
```

## 4. Livewireアニメーション

### 4.1 ローディングアニメーション
```blade
{{-- Livewireローディング状態 --}}
<div wire:loading class="loading-overlay">
    <div class="loading-spinner">
        <x-mary-loading class="w-8 h-8 text-primary-600" />
        <p class="mt-2 text-sm text-gray-600">読み込み中...</p>
    </div>
</div>

{{-- コンテンツのフェードアウト --}}
<div wire:loading.class="opacity-50 pointer-events-none">
    {{ $content }}
</div>
```

### 4.2 スケルトンローディング
```blade
{{-- スケルトンローディング --}}
<div wire:loading.remove>
    {{-- 実際のコンテンツ --}}
    @foreach($items as $item)
        <x-menu-item-card :item="$item" />
    @endforeach
</div>

<div wire:loading>
    {{-- スケルトンコンテンツ --}}
    @for($i = 0; $i < 6; $i++)
        <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-content">
                <div class="skeleton-line w-3/4"></div>
                <div class="skeleton-line w-1/2"></div>
                <div class="skeleton-line w-1/4"></div>
            </div>
        </div>
    @endfor
</div>
```

### 4.3 スケルトンスタイル
```css
.skeleton-card {
  @apply bg-white rounded-card border border-gray-200 overflow-hidden;
  @apply animate-pulse;
}

.skeleton-image {
  @apply bg-gray-200 aspect-square;
}

.skeleton-content {
  @apply p-3 space-y-2;
}

.skeleton-line {
  @apply h-4 bg-gray-200 rounded;
}

/* Pulseアニメーションのカスタマイズ */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.6;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
```

## 5. フィードバックアニメーション

### 5.1 ボタンアニメーション
```css
/* ボタンのホバーエフェクト */
.btn-interactive {
  @apply transition-all duration-fast ease-smooth;
  @apply hover:shadow-md hover:-translate-y-0.5;
  @apply active:transform active:scale-95;
  @apply focus:ring-2 focus:ring-primary-500 focus:ring-offset-2;
}

/* ボタンのクリックアニメーション */
.btn-click-effect {
  @apply relative overflow-hidden;
}

.btn-click-effect::after {
  content: '';
  @apply absolute inset-0 bg-white opacity-0;
  @apply transition-opacity duration-fast;
}

.btn-click-effect:active::after {
  @apply opacity-20;
}
```

### 5.2 カードホバー
```css
.card-hover {
  @apply transition-all duration-normal ease-smooth;
  @apply hover:shadow-card-hover;
  @apply hover:-translate-y-1;
  @apply cursor-pointer;
}

.card-hover:hover {
  @apply transform;
}
```

### 5.3 フォームフィールドアニメーション
```css
.form-field {
  @apply transition-all duration-normal ease-smooth;
}

.form-field:focus-within {
  @apply transform scale-[1.02];
}

.form-input {
  @apply transition-colors duration-fast ease-smooth;
}

.form-input:focus {
  @apply border-primary-500 ring-2 ring-primary-200;
}
```

## 6. プログレスアニメーション

### 6.1 注文進行状況
```blade
{{-- 注文ステップインジケーター --}}
<div class="progress-steps">
    @foreach(['menu', 'cart', 'checkout', 'complete'] as $index => $step)
        <div class="progress-step {{ $index <= $currentStep ? 'active' : '' }}">
            <div class="step-circle">
                @if($index < $currentStep)
                    <x-mary-icon name="o-check" class="w-4 h-4" />
                @else
                    {{ $index + 1 }}
                @endif
            </div>
            <span class="step-label">{{ ucfirst($step) }}</span>
        </div>
        
        @if(!$loop->last)
            <div class="step-connector {{ $index < $currentStep ? 'completed' : '' }}"></div>
        @endif
    @endforeach
</div>
```

### 6.2 プログレスバー
```css
.progress-bar {
  @apply w-full bg-gray-200 rounded-full h-2;
  @apply overflow-hidden;
}

.progress-fill {
  @apply bg-primary-600 h-full rounded-full;
  @apply transition-all duration-slow ease-smooth;
  @apply transform origin-left;
}

/* アニメーション付きプログレス */
@keyframes progress-animation {
  0% {
    transform: scaleX(0);
  }
  100% {
    transform: scaleX(var(--progress, 0));
  }
}

.progress-animated {
  animation: progress-animation 1s ease-out forwards;
}
```

## 7. アクセシビリティ考慮

### 7.1 モーション減少設定
```css
/* ユーザーがアニメーションを無効化している場合 */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
  
  .animate-pulse {
    animation: none;
  }
  
  .hover-lift:hover {
    transform: none;
  }
}
```

### 7.2 フォーカス表示
```css
.focus-visible {
  @apply transition-all duration-fast ease-smooth;
}

.focus-visible:focus {
  @apply outline-none ring-2 ring-primary-500 ring-offset-2;
}

/* キーボードナビゲーション時のみフォーカス表示 */
.focus-visible:focus:not(.focus-visible) {
  @apply ring-0;
}
```

## 8. パフォーマンス最適化

### 8.1 CSSアニメーションの最適化
```css
/* GPUアクセラレーションを促進 */
.gpu-accelerated {
  @apply transform-gpu;
  will-change: transform, opacity;
}

/* アニメーション終了後のwill-changeをリセット */
.animation-finished {
  will-change: auto;
}

/* 高パフォーマンスアニメーション */
.performance-optimized {
  @apply transition-transform transition-opacity;
  /* transformとopacityのみ使用し、layoutやpaintを発生させない */
}
```

### 8.2 JavaScriptでの最適化
```javascript
// アニメーションパフォーマンス監視
document.addEventListener('alpine:init', () => {
    Alpine.directive('performance-transition', (el, { expression }, { evaluate }) => {
        // パフォーマンスを重視したアニメーション実装
        el.style.willChange = 'transform, opacity';
        
        // アニメーション終了後のクリーンアップ
        el.addEventListener('transitionend', () => {
            el.style.willChange = 'auto';
        });
    });
});
```

## 9. 実装ガイドライン

### 9.1 アニメーションの選択基準
アニメーションを追加する前に、以下の質問に答える：

1. **このアニメーションは機能的な目的があるか？**
   - YES: 実装を検討
   - NO: 実装しない

2. **ユーザーの理解や操作に役立つか？**
   - YES: 実装を検討
   - NO: 実装しない

3. **パフォーマンスへの影響は許容範囲か？**
   - YES: 実装を検討
   - NO: 実装しない

### 9.2 ベストプラクティス
1. **最短時間で最大の効果**: 200ms以内で終了するアニメーションを優先
2. **transformとopacityのみ使用**: レイアウトのrecalculationを回避
3. **will-changeプロパティの適切な管理**: アニメーション開始時に設定、終了時にリセット
4. **prefers-reduced-motionへの対応**: 必須対応
5. **バッテリー節約**: モバイルデバイスでのバッテリー消費を考慮

### 9.3 テスト方法
```javascript
// アニメーションのパフォーマンステスト
const measureAnimationPerformance = () => {
    const start = performance.now();
    
    // アニメーション実行
    
    requestAnimationFrame(() => {
        const end = performance.now();
        console.log(`Animation duration: ${end - start}ms`);
        
        // 16.67ms (60fps)を上回っているかチェック
        if (end - start > 16.67) {
            console.warn('Animation may cause frame drops');
        }
    });
};
```

---

このアニメーションシステム設計書に従って、機能的で必要最小限のアニメーションのみを実装し、ユーザーエクスペリエンスとパフォーマンスのバランスを保ちます。モバイルデバイスでのバッテリー消費とCPU使用量を最小限に抑えながら、必要なフィードバックを提供します。