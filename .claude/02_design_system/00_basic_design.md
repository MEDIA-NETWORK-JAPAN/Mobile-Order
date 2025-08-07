# デザインシステム基本設計書

## 1. デザインシステム概要

### 1.1 目的
モバイルオーダーシステムにおける一貫したユーザーエクスペリエンスを提供するため、デザインの原則、コンポーネント、スタイルを体系化し、開発効率と品質の向上を図ります。

### 1.2 技術スタック
- **Livewire 3.6+**: リアクティブコンポーネント
- **Mary UI v2.4+**: TailwindCSSベースのUIライブラリ
- **TailwindCSS 4.0**: ユーティリティファーストCSS
- **Alpine.js**: Livewireにバンドル、軽量なクライアントサイドインタラクション

### 1.3 設計方針
- **モバイルファースト**: スマートフォンでの使用を最優先
- **シンプル**: 不要な装飾を排除し、機能に集中
- **一貫性**: 全ての画面で統一された体験
- **アクセシビリティ**: WCAG 2.1 AAレベル準拠
- **パフォーマンス**: 軽量で高速な表示

## 2. デザインシステム構成

### 2.1 ファイル構成
```
.claude/02_design_system/
├── 00_basic_design.md           # 基本設計（このファイル）
├── 01_design_principles.md      # デザイン原則
├── 02_component_design.md       # コンポーネント設計
├── 03_animation_system.md       # アニメーションシステム
└── 04_layout_system.md          # レイアウトシステム
```

### 2.2 責任範囲
- **デザイン原則**: カラー、タイポグラフィ、スペーシングの基本ルール
- **コンポーネント設計**: Mary UIを活用した再利用可能なUIコンポーネント
- **レイアウトシステム**: モバイル最適化されたグリッドとレスポンシブデザイン
- **アニメーション**: 必要最小限の機能的アニメーション

## 3. デザイントークン体系

### 3.1 基本概念
デザイントークンは、TailwindCSSの設定ファイルとして管理し、全てのスタイルの単一情報源とします。

```javascript
// tailwind.config.js の基本構造
module.exports = {
  theme: {
    extend: {
      colors: { /* カラーパレット */ },
      fontFamily: { /* フォント設定 */ },
      fontSize: { /* フォントサイズ */ },
      spacing: { /* スペーシング */ },
      borderRadius: { /* 角丸 */ },
      screens: { /* ブレークポイント */ },
    },
  },
}
```

### 3.2 Mary UIとの統合
Mary UIのデフォルト設定を尊重しつつ、プロジェクト固有のカスタマイズを追加します。

```php
// config/mary.php での設定例
return [
    'toast' => [
        'position' => 'toast-top toast-end',
        'timeout' => 3000,
    ],
    'modal' => [
        'backdrop_class' => 'backdrop-blur-sm',
    ],
];
```

## 4. モバイルファースト設計

### 4.1 対象デバイス
- **プライマリ**: スマートフォン（320px～480px）
- **セカンダリ**: タブレット（481px～1024px）
- **サポート**: デスクトップ（1025px以上）

### 4.2 設計原則
- **タッチフレンドリー**: 最小タッチターゲット44px×44px
- **片手操作**: 重要な操作は画面下部に配置
- **スクロール最小化**: 1画面に必要な情報をコンパクトに配置
- **高速表示**: 画像最適化とプリロード戦略

## 5. 品質基準

### 5.1 デザイン品質
- **一貫性**: 同一要素は同一スタイル
- **対比**: 重要度に応じた視覚的階層
- **余白**: 適切なホワイトスペースの活用
- **可読性**: 十分なコントラスト比の確保

### 5.2 技術品質
- **パフォーマンス**: CSSファイルサイズ最小化
- **保守性**: コンポーネント化による再利用
- **拡張性**: 新機能追加時の設計パターン継承
- **互換性**: 各ブラウザーでの一貫した表示

## 6. 実装ガイドライン

### 6.1 Livewireコンポーネントでの使用
```php
// app/Livewire/Customer/MenuCard.php
class MenuCard extends Component
{
    public Product $item;
    
    public function render()
    {
        return view('livewire.customer.menu-card');
    }
}
```

```blade
{{-- resources/views/livewire/customer/menu-card.blade.php --}}
<div class="card-base shadow-card">
    <img src="{{ $item->image_url }}" 
         alt="{{ $item->name }}"
         class="aspect-square object-cover rounded-lg">
    
    <div class="card-content">
        <h3 class="text-heading-sm font-semibold">{{ $item->name }}</h3>
        <p class="text-body-sm text-gray-600">{{ $item->description }}</p>
        
        <div class="flex justify-between items-center mt-4">
            <span class="text-price font-bold text-primary-600">
                ¥{{ number_format($item->price) }}
            </span>
            <x-mary-button 
                wire:click="addToCart"
                class="btn-primary"
                size="sm"
            >
                カートに追加
            </x-mary-button>
        </div>
    </div>
</div>
```

### 6.2 カスタムCSS定義
```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer components {
  .card-base {
    @apply bg-white rounded-lg border border-gray-200 overflow-hidden;
  }
  
  .card-content {
    @apply p-4;
  }
  
  .shadow-card {
    @apply shadow-sm hover:shadow-md transition-shadow duration-200;
  }
  
  .btn-primary {
    @apply bg-primary-600 hover:bg-primary-700 text-white;
  }
  
  .text-heading-sm {
    @apply text-lg leading-tight;
  }
  
  .text-body-sm {
    @apply text-sm leading-relaxed;
  }
  
  .text-price {
    @apply text-xl;
  }
}
```

## 7. ブラウザバック無効化実装

### 7.1 基本実装パターン
```javascript
// Alpine.js コンポーネントとして実装
Alpine.data('preventBrowserBack', () => ({
    init() {
        // 履歴を操作して戻るボタンを無効化
        this.addHistoryState();
        
        // popstateイベントをリッスン（サイレント処理）
        window.addEventListener('popstate', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.addHistoryState();
            return false;
        });
    },
    
    addHistoryState() {
        window.history.pushState(
            { preventBack: true }, 
            document.title, 
            window.location.href
        );
    }
}))
```

### 7.2 Livewire統合
```blade
{{-- レイアウトファイルに追加 --}}
<body x-data="preventBrowserBack" class="bg-gray-50">
    {{ $slot }}
    
    {{-- アプリ内ナビゲーション --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t">
        <button wire:click="navigateBack" class="p-4">
            <svg><!-- 戻るアイコン --></svg>
            <span>戻る</span>
        </button>
    </nav>
</body>
```

## 8. Mary UIカスタマイズ戦略

### 8.1 推奨アプローチ
1. **Mary UIデフォルトを尊重**: 可能な限りデフォルト設定を使用
2. **必要最小限のカスタマイズ**: プロジェクト固有の要件のみ調整
3. **TailwindCSSクラスでの拡張**: Mary UIコンポーネントにクラス追加
4. **設定ファイルでのグローバル調整**: config/mary.phpを活用

### 8.2 カスタマイズ例
```blade
{{-- Mary UIコンポーネントのカスタマイズ --}}
<x-mary-card class="mobile-card">
    <x-slot:title class="text-heading-sm">
        {{ $title }}
    </x-slot:title>
    
    <div class="card-content">
        {{ $slot }}
    </div>
</x-mary-card>

{{-- Mary UI Alert のカスタマイズ --}}
<x-mary-alert 
    type="success" 
    class="mobile-alert"
    dismissible
    timeout="3000"
>
    {{ $message }}
</x-mary-alert>
```

## 9. 開発フロー

### 9.1 新規コンポーネント作成時
1. **デザイン原則の確認**: カラー、フォント、スペーシング規則に準拠
2. **Mary UIコンポーネント検索**: 既存コンポーネントで要件を満たせるか確認
3. **モバイルファースト実装**: スマートフォンでの表示を最初に実装
4. **レスポンシブ対応**: タブレット、デスクトップでの表示調整
5. **アクセシビリティ確認**: キーボード操作、スクリーンリーダー対応

### 9.2 品質チェックリスト
- [ ] モバイルでの表示が適切
- [ ] タッチターゲットが44px以上
- [ ] コントラスト比が適切
- [ ] Mary UIパターンに準拠
- [ ] TailwindCSSクラスを適切に使用
- [ ] 不要なカスタムCSSを使用していない
- [ ] ブラウザバック無効化が機能している

## 10. 継続的改善

### 10.1 定期レビュー
- **月次**: デザインシステムの使用状況確認
- **四半期**: ユーザビリティテストの結果反映
- **年次**: 技術スタックアップデートへの対応

### 10.2 改善プロセス
1. **課題の特定**: 開発者フィードバック、ユーザビリティテスト
2. **解決策の検討**: 技術制約、デザイン一貫性の考慮
3. **プロトタイプ作成**: 小規模な実装とテスト
4. **ドキュメント更新**: 設計書、ガイドラインの更新
5. **チーム周知**: 変更点の共有と教育

---

このデザインシステム基本設計書に基づいて、各詳細ドキュメントで具体的な実装ガイドラインを定義していきます。モバイルファーストのアプローチと現在の技術スタックを活用し、効率的で一貫した開発を実現します。