# デザイン原則書

## 1. カラーシステム

### 1.1 プライマリカラー（アンバー）
食べ物と美味しさを連想させる温かみのあるアンバー系をメインカラーとして採用。

```javascript
// tailwind.config.js
colors: {
  primary: {
    50: '#fefbec',   // 極淡いアンバー（背景用）
    100: '#fef3c7',  // 淡いアンバー（ホバー）
    200: '#fde68a',  // 明るいアンバー
    300: '#fcd34d',  // 中間のアンバー
    400: '#fbbf24',  // 少し深いアンバー
    500: '#f59e0b',  // 標準アンバー（メイン）
    600: '#d97706',  // 深いアンバー（ボタン等）
    700: '#b45309',  // より深いアンバー
    800: '#92400e',  // 暗いアンバー
    900: '#78350f',  // 最も暗いアンバー
  },
}
```

### 1.2 セカンダリカラー（グレー）
テキスト、ボーダー、背景などに使用するニュートラルなグレーシステム。

```javascript
colors: {
  gray: {
    50: '#f9fafb',   // 極淡いグレー（背景）
    100: '#f3f4f6',  // 淡いグレー（カード背景）
    200: '#e5e7eb',  // 明るいグレー（ボーダー）
    300: '#d1d5db',  // 中間のグレー
    400: '#9ca3af',  // グレー（アイコン等）
    500: '#6b7280',  // 暗めのグレー
    600: '#4b5563',  // 深いグレー（サブテキスト）
    700: '#374151',  // より深いグレー
    800: '#1f2937',  // 暗いグレー（メインテキスト）
    900: '#111827',  // 最も暗いグレー
  },
}
```

### 1.3 システムカラー
ユーザーにステータスやフィードバックを伝えるためのシステムカラー。

```javascript
colors: {
  // 成功・完了
  success: {
    50: '#ecfdf5',
    100: '#d1fae5',
    500: '#10b981',  // メイン成功色
    600: '#059669',
    700: '#047857',
  },
  
  // 警告・注意
  warning: {
    50: '#fffbeb',
    100: '#fef3c7',
    500: '#f59e0b',  // メイン警告色
    600: '#d97706',
    700: '#b45309',
  },
  
  // エラー・危険
  error: {
    50: '#fef2f2',
    100: '#fee2e2',
    500: '#ef4444',  // メインエラー色
    600: '#dc2626',
    700: '#b91c1c',
  },
  
  // 情報・ニュートラル
  info: {
    50: '#eff6ff',
    100: '#dbeafe',
    500: '#3b82f6',  // メイン情報色
    600: '#2563eb',
    700: '#1d4ed8',
  },
}
```

### 1.4 カラー使用ガイドライン

#### プライマリカラーの使用
- **primary-600**: メインボタン、CTA要素
- **primary-700**: ホバー状態
- **primary-500**: アクセント、リンク
- **primary-100**: 背景ハイライト
- **primary-50**: 微細な背景色

#### グレーシステムの使用
- **gray-900**: メインテキスト
- **gray-600**: サブテキスト
- **gray-400**: プレースホルダー、アイコン
- **gray-200**: ボーダー、区切り線
- **gray-50**: カード背景、サイドバー

## 2. タイポグラフィ

### 2.1 フォントファミリー
モバイルデバイスでの可読性を重視し、システムフォントを基本としたフォントスタック。

```javascript
// tailwind.config.js
fontFamily: {
  'sans': [
    // 日本語フォント
    'Hiragino Kaku Gothic ProN',
    'Hiragino Sans',
    'Yu Gothic Medium',
    'Meiryo',
    // 英語フォント
    'system-ui',
    '-apple-system',
    'BlinkMacSystemFont',
    'Segoe UI',
    'Roboto',
    'Helvetica Neue',
    'Arial',
    // フォールバック
    'sans-serif',
  ],
},
```

### 2.2 フォントサイズシステム
モバイルファーストで読みやすさを確保したフォントサイズ設定。

```javascript
// tailwind.config.js
fontSize: {
  // 基本テキスト
  'xs': ['0.75rem', { lineHeight: '1rem' }],      // 12px
  'sm': ['0.875rem', { lineHeight: '1.25rem' }],  // 14px
  'base': ['1rem', { lineHeight: '1.5rem' }],     // 16px (デフォルト)
  'lg': ['1.125rem', { lineHeight: '1.75rem' }],  // 18px
  'xl': ['1.25rem', { lineHeight: '1.75rem' }],   // 20px
  
  // 見出し用
  '2xl': ['1.5rem', { lineHeight: '2rem' }],      // 24px
  '3xl': ['1.875rem', { lineHeight: '2.25rem' }], // 30px
  '4xl': ['2.25rem', { lineHeight: '2.5rem' }],   // 36px
  
  // 特殊用途
  'price': ['1.5rem', { lineHeight: '2rem', fontWeight: '700' }], // 価格表示
}
```

### 2.3 フォントウェイト
情報の階層を明確にするためのウェイトシステム。

```javascript
fontWeight: {
  'normal': '400',    // 通常テキスト
  'medium': '500',    // 少し太いテキスト
  'semibold': '600',  // サブタイトル、重要なテキスト
  'bold': '700',      // 見出し、メインタイトル
  'extrabold': '800', // 特に強調したい要素
},
```

### 2.4 タイポグラフィスケール
モバイルデバイスでの読みやすさを考慮した文字サイズの階層。

```css
/* タイポグラフィスケール例 */
.text-display-lg { @apply text-4xl font-bold; }      /* メインタイトル */
.text-display-md { @apply text-3xl font-bold; }      /* サブタイトル */
.text-display-sm { @apply text-2xl font-semibold; }  /* セクションタイトル */

.text-heading-lg { @apply text-xl font-semibold; }   /* 大きな見出し */
.text-heading-md { @apply text-lg font-semibold; }   /* 中くらいの見出し */
.text-heading-sm { @apply text-base font-semibold; } /* 小さな見出し */

.text-body-lg { @apply text-lg font-normal; }        /* 大きな本文 */
.text-body-md { @apply text-base font-normal; }      /* 標準本文 */
.text-body-sm { @apply text-sm font-normal; }        /* 小さな本文 */

.text-caption { @apply text-xs font-normal; }        /* キャプション、補助テキスト */
.text-price { @apply text-xl font-bold; }            /* 価格表示 */
```

### 2.5 行間と文字間隔
読みやすさを向上させるための適切なスペーシング。

```javascript
// tailwind.config.js
lineHeight: {
  'tight': '1.25',     // 緊密な行間（見出し用）
  'snug': '1.375',     // 少し緊密な行間
  'normal': '1.5',     // 標準行間（本文用）
  'relaxed': '1.625',  // リラックスした行間
  'loose': '2',        // 幅広な行間（特別な場合）
},

letterSpacing: {
  'tighter': '-0.05em',  // より緊密
  'tight': '-0.025em',   // 緊密
  'normal': '0em',       // 標準
  'wide': '0.025em',     // 幅広
  'wider': '0.05em',     // より幅広
},
```

## 3. スペーシングシステム

### 3.1 基本单位
4pxを基本単位とした一貫したスペーシングシステム。

```javascript
// tailwind.config.js
spacing: {
  'px': '1px',
  '0': '0px',
  '0.5': '0.125rem',  // 2px
  '1': '0.25rem',     // 4px  - 基本単位
  '1.5': '0.375rem',  // 6px
  '2': '0.5rem',      // 8px  - 小さな間隔
  '2.5': '0.625rem',  // 10px
  '3': '0.75rem',     // 12px - コンポーネント内スペーシング
  '3.5': '0.875rem',  // 14px
  '4': '1rem',        // 16px - 標準スペーシング
  '5': '1.25rem',     // 20px
  '6': '1.5rem',      // 24px - セクション間隔
  '7': '1.75rem',     // 28px
  '8': '2rem',        // 32px - 大きなセクション間
  '9': '2.25rem',     // 36px
  '10': '2.5rem',     // 40px
  '11': '2.75rem',    // 44px - タッチターゲットサイズ
  '12': '3rem',       // 48px - ページレベルの間隔
  '14': '3.5rem',     // 56px
  '16': '4rem',       // 64px
  '20': '5rem',       // 80px
  '24': '6rem',       // 96px
  '32': '8rem',       // 128px
},
```

### 3.2 スペーシングガイドライン

#### コンポーネント内スペーシング
```css
/* コンポーネント内の要素間 */
.space-xs { @apply space-y-1; }   /* 4px - 密接した要素 */
.space-sm { @apply space-y-2; }   /* 8px - 関連する要素 */
.space-md { @apply space-y-3; }   /* 12px - 標準的な間隔 */
.space-lg { @apply space-y-4; }   /* 16px - 少し幅広な間隔 */
.space-xl { @apply space-y-6; }   /* 24px - 大きな間隔 */
```

#### パディングシステム
```css
/* コンポーネント内のパディング */
.p-xs { @apply p-2; }    /* 8px - コンパクトなボタン */
.p-sm { @apply p-3; }    /* 12px - 小さなカード */
.p-md { @apply p-4; }    /* 16px - 標準カード */
.p-lg { @apply p-6; }    /* 24px - 大きなカード */
.p-xl { @apply p-8; }    /* 32px - メインコンテンツ */
```

#### マージンシステム
```css
/* コンポーネント間のマージン */
.m-xs { @apply m-2; }    /* 8px - 関連要素 */
.m-sm { @apply m-3; }    /* 12px - コンポーネント間 */
.m-md { @apply m-4; }    /* 16px - 標準間隔 */
.m-lg { @apply m-6; }    /* 24px - セクション間 */
.m-xl { @apply m-8; }    /* 32px - ページレベル */
```

### 3.3 モバイル特化スペーシング
モバイルデバイスでの使いやすさを考慮した特別なスペーシング。

```css
/* モバイル特化スペーシング */
.touch-target-min { @apply min-h-11 min-w-11; }  /* 44px - 最小タッチターゲット */
.touch-target-comfortable { @apply min-h-12 min-w-12; } /* 48px - 快適なタッチターゲット */

.mobile-padding { @apply p-4 sm:p-6; }          /* モバイル用パディング */
.mobile-margin { @apply m-4 sm:m-6; }           /* モバイル用マージン */
.mobile-gap { @apply gap-4 sm:gap-6; }          /* モバイル用ギャップ */
```

## 4. シャドウシステム

### 4.1 深度表現
要素の重要度や階層を表現するためのシャドウシステム。

```javascript
// tailwind.config.js
boxShadow: {
  'none': 'none',
  'sm': '0 1px 2px 0 rgb(0 0 0 / 0.05)',           // 微細な影
  'DEFAULT': '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)', // 標準影
  'md': '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',   // 中程度の影
  'lg': '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)', // 大きな影
  'xl': '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)', // より大きな影
  
  // モバイル特化シャドウ
  'card': '0 2px 4px 0 rgb(0 0 0 / 0.06)',         // カード用
  'card-hover': '0 4px 8px 0 rgb(0 0 0 / 0.12)',  // カードホバー用
  'button': '0 1px 2px 0 rgb(0 0 0 / 0.05)',       // ボタン用
  'modal': '0 25px 50px -12px rgb(0 0 0 / 0.25)',  // モーダル用
},
```

### 4.2 シャドウ使用ガイドライン
```css
/* シャドウの使い分け */
.elevation-0 { @apply shadow-none; }       /* フラットな要素 */
.elevation-1 { @apply shadow-sm; }        /* 軽い浮き上がり */
.elevation-2 { @apply shadow-card; }      /* カード、パネル */
.elevation-3 { @apply shadow-md; }        /* ドロップダウン */
.elevation-4 { @apply shadow-lg; }        /* ダイアログ */
.elevation-5 { @apply shadow-modal; }     /* モーダル、オーバーレイ */

/* ホバーエフェクト */
.hover-lift {
  @apply transition-shadow duration-200 ease-out;
  @apply hover:shadow-card-hover;
}
```

## 5. ボーダーラディウス

### 5.1 角丸システム
モバイルフレンドリーなデザインのための適切な角丸設定。

```javascript
// tailwind.config.js
borderRadius: {
  'none': '0px',
  'sm': '0.125rem',    // 2px - 小さな要素
  'DEFAULT': '0.25rem', // 4px - 標準角丸
  'md': '0.375rem',    // 6px - カード、ボタン
  'lg': '0.5rem',      // 8px - 大きなカード
  'xl': '0.75rem',     // 12px - 特別なカード
  '2xl': '1rem',       // 16px - 大きなパネル
  '3xl': '1.5rem',     // 24px - 特別なデザイン
  'full': '9999px',    // 完全な円形
  
  // コンポーネント用
  'button': '0.375rem',  // 6px - ボタン用
  'card': '0.5rem',      // 8px - カード用
  'input': '0.375rem',   // 6px - 入力フィールド用
  'modal': '0.75rem',    // 12px - モーダル用
},
```

### 5.2 角丸使用ガイドライン
```css
/* コンポーネント別角丸 */
.rounded-button { @apply rounded-button; }     /* ボタン */
.rounded-card { @apply rounded-card; }         /* カード */
.rounded-input { @apply rounded-input; }       /* 入力フィールド */
.rounded-image { @apply rounded-lg; }          /* 画像 */
.rounded-avatar { @apply rounded-full; }       /* アバター */
.rounded-badge { @apply rounded-full; }        /* バッジ */
```

## 6. アクセシビリティ考慮

### 6.1 コントラスト比
テキストの可読性を確保するためのコントラスト比設定。

```css
/* WCAG AAレベル準拠のコントラスト比 */
.text-high-contrast { @apply text-gray-900; }     /* 21:1 - メインテキスト */
.text-medium-contrast { @apply text-gray-700; }   /* 10:1 - サブテキスト */
.text-low-contrast { @apply text-gray-600; }      /* 7:1 - 補助テキスト */
.text-disabled { @apply text-gray-400; }          /* 3:1 - 無効状態 */

/* 背景色との組み合わせ */
.on-white { @apply text-gray-900; }               /* 白背景用 */
.on-primary { @apply text-white; }                /* プライマリ背景用 */
.on-dark { @apply text-white; }                   /* 暗い背景用 */
```

### 6.2 フォーカス表示
キーボードナビゲーションのためのフォーカス表示。

```css
/* フォーカススタイル */
.focus-visible {
  @apply focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2;
}

.focus-visible-inset {
  @apply focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset;
}

/* コンポーネント別フォーカス */
.button-focus { @apply focus-visible; }
.input-focus { @apply focus-visible-inset; }
.card-focus { @apply focus-visible; }
```

---

このデザイン原則書に従って、一貫したモバイルファーストのユーザーインターフェースを構築できます。全ての設定はTailwindCSSの設定ファイルで管理し、Mary UIコンポーネントとのシームレスな統合を実現します。