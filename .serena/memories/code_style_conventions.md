# Mobile Order System - コードスタイル・規約

## PHPコーディング規約
- **PSR-12**準拠
- **Laravel Pint**でコード整形（設定はデフォルト）
- 名前空間: `App\` プレフィックス使用

## Livewireコンポーネント規約
### 必須トレイト
```php
use Mary\Traits\Toast;      // トースト通知
use Livewire\WithPagination; // ページネーション
```

### プロパティ命名規則
```php
// 権限制御
public $canEdit = false;

// フィルター関連
public $search = '';
public $selectedCategory = '';
public $selectedStore = '';
public $availabilityFilter = '';

// ソート関連
public $sortField = 'id';
public $sortDirection = 'desc';
```

### mount()メソッド
```php
public function mount() {
    $this->canEdit = auth()->user()->isSuperAdmin();
}
```

## 状態管理パターン
### リダイレクト方式（推奨）
```php
// OK: リダイレクトで確実な状態リセット
return redirect()->route('admin.products.index');

// NG: プロパティリセットのみ（状態が不安定）
$this->selectedItems = [];
```

## Pivotテーブル操作
```php
// ソート順付きの関連付け
$maxSortOrder = $this->product->options()->max('product_to_options.sort_order') ?? 0;
$attachData = [];
foreach ($selectedOptions as $index => $optionId) {
    $attachData[$optionId] = ['sort_order' => $maxSortOrder + $index + 1];
}
$this->product->options()->attach($attachData);

// ソート指定
->orderByPivot('sort_order') // 正しい
// NG: ->orderBy('pivot_sort_order')
```

## エラーハンドリング
### 商品削除時のコード再利用対応
```php
$deletedCode = 'DELETED_' . time() . '_' . $product->code;
$product->update(['code' => $deletedCode]);
$product->delete(); // SoftDelete
```

## 管理画面UIパターン
### Index画面
- フィルター機能（検索・カテゴリ・状態・店舗）
- クリアフィルター機能（redirect方式）
- ソート機能（カラムクリック）
- ページネーション
- 権限別表示（SuperAdmin/Admin/閲覧のみ）

### Edit画面
- 基本情報編集
- 関連データ管理（3カラムUI: 未割当 ← 操作 → 割当済み）
- ソート機能（ドラッグ&ドロップ風UI）
- 一括操作（選択→割当/解除）
- リダイレクト方式での状態管理

## ディレクトリ構造
```
app/
├── Http/Controllers/    # API・Webコントローラー
├── Http/Middleware/     # 認証・権限チェック
├── Livewire/           # Livewireコンポーネント
├── Models/             # Eloquentモデル
├── Services/           # ビジネスロジック
└── Policies/           # 権限管理

resources/views/
├── livewire/           # Livewireビュー
└── components/         # Bladeコンポーネント
```

## 命名規則
- **クラス名**: PascalCase（例: ProductController）
- **メソッド名**: camelCase（例: getProducts）
- **変数名**: camelCase（例: $productList）
- **定数**: UPPER_SNAKE_CASE（例: MAX_ITEMS）
- **テーブル名**: 複数形snake_case（例: products）
- **カラム名**: snake_case（例: created_at）

## その他の規約
- **コメント禁止**: コメントは追加しない（ユーザーから明示的に要求された場合を除く）
- **型ヒント**: PHP 8.1の型ヒントを積極的に使用
- **Null合体演算子**: `??` の使用を推奨
- **アロー関数**: 適切な場面で使用