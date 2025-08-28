# SuperAdmin緊急編集機能 実装ドキュメント

## 📚 目次

- [1. 概要](#1-概要)
- [2. 実装背景](#2-実装背景)
- [3. 権限設計](#3-権限設計)
- [4. 実装詳細](#4-実装詳細)
- [5. ビュー実装](#5-ビュー実装)
- [6. 運用方法](#6-運用方法)
- [7. セキュリティ考慮事項](#7-セキュリティ考慮事項)

---

## 1. 概要

### 目的
POS中心設計において、緊急時（POS障害等）にSuperAdminのみがクラウド側から商品・カテゴリ・オプションのマスターデータを編集できる機能を実装。

### 実装方針
- **通常時**: 一般管理者は閲覧のみ（設計書通り）
- **緊急時**: SuperAdminのみCUD操作可能
- **同期**: POS側への反映は手動

## 2. 実装背景

### 設計書との整合性確保
当初、Phase 2-1で実装されたコンポーネントは一般的なCRUD機能として作成されていたが、これは設計書のPOS中心設計と矛盾していた。

**設計書の要件**:
- マスターデータの管理主体はPOS端末
- クラウド側は基本的に閲覧のみ
- データ更新はPOS → クラウドの一方向

**実装時の課題**:
- 緊急時対応の必要性
- 既存コンポーネントの活用
- 権限管理の複雑性

### 解決策
SuperAdmin権限による緊急編集機能として位置づけ、設計書の原則を守りながら運用上の柔軟性を確保。

## 3. 権限設計

### 権限レベル
```php
// app/Models/User.php
public function isSuperAdmin()
{
    return $this->role === 'super_admin';
}

public function isAdmin() 
{
    return in_array($this->role, ['super_admin', 'admin']);
}
```

### 権限マトリックス
| 機能 | SuperAdmin | Admin | Staff | POS System |
|------|------------|-------|-------|------------|
| 商品閲覧 | ✅ | ✅ | ✅ | ✅ |
| 商品編集 | ✅（緊急時） | ❌ | ❌ | ❌ |
| 商品作成 | ✅（緊急時） | ❌ | ❌ | ❌ |
| 商品削除 | ✅（緊急時） | ❌ | ❌ | ❌ |
| カテゴリ編集 | ✅（緊急時） | ❌ | ❌ | ❌ |

## 4. 実装詳細

### 4.1 ProductForm.php の権限制御

#### save()メソッドの修正
```php
public function save()
{
    $this->validate();

    $user = auth()->user();
    
    // SuperAdmin権限チェック（緊急編集機能）
    if (!$user->isSuperAdmin()) {
        $this->error('商品の編集権限がありません。商品マスターデータはPOS側で管理されています。緊急編集にはSuperAdmin権限が必要です。');
        return;
    }
    
    // 既存の処理...
}
```

#### render()メソッドの修正
```php
public function render()
{
    $user = auth()->user();
    
    return view('livewire.admin.products.product-form', [
        'stores' => $stores,
        'categories' => $categories,
        'canEdit' => $user->isSuperAdmin(), // 編集権限フラグ
    ]);
}
```

### 4.2 CategoryCreate.php の権限制御

#### save()メソッドの修正
```php
public function save()
{
    $this->validate();

    $user = auth()->user();
    
    // SuperAdmin権限チェック（緊急編集機能）
    if (!$user->isSuperAdmin()) {
        $this->error('カテゴリの編集権限がありません。カテゴリマスターデータはPOS側で管理されています。緊急編集にはSuperAdmin権限が必要です。');
        return;
    }
    
    // 既存の処理...
}
```

### 4.3 CategoryIndex.php の権限制御

#### 複数メソッドに権限チェック追加
```php
public function editCategory($categoryId)
{
    // SuperAdmin権限チェック（緊急編集機能）
    if (!$user->isSuperAdmin()) {
        $this->error('カテゴリの編集権限がありません。カテゴリマスターデータはPOS側で管理されています。');
        return;
    }
    // 既存の処理...
}

public function toggleActive($categoryId)
{
    // 同様の権限チェック
}

public function deleteCategory($categoryId)
{
    // 同様の権限チェック
}
```

### 4.4 ProductIndex.php の修正

#### canEditフラグの追加
```php
public function render()
{
    $user = auth()->user();
    
    return view('livewire.admin.products.product-index', [
        'products' => $products,
        'stores' => $stores,
        'categories' => $categories,
        'canEdit' => $user->isSuperAdmin(), // 編集権限フラグ
    ]);
}
```

## 5. ビュー実装

### 5.1 権限別メッセージ表示

#### SuperAdmin向け警告メッセージ
```blade
@if($canEdit)
    <x-mary-alert type="warning" dismissible="false">
        <strong>SuperAdmin緊急編集モード</strong><br>
        商品マスターデータは通常POS側で管理されています。<br>
        緊急編集後は必ずPOS側のデータを手動で同期してください。
    </x-mary-alert>
@else
    <x-mary-alert type="info" dismissible="false">
        商品データはPOS側で管理されています。こちらは閲覧専用です。<br>
        編集が必要な場合は、POS端末から操作してください。
    </x-mary-alert>
@endif
```

### 5.2 ボタン表示制御

#### 商品一覧画面
```blade
<td>
    @if($canEdit)
        <x-mary-button wire:click="edit({{ $product->id }})" size="sm" class="btn-ghost">
            編集
        </x-mary-button>
        <x-mary-button wire:click="delete({{ $product->id }})" size="sm" class="btn-ghost text-error">
            削除
        </x-mary-button>
    @else
        <x-mary-button wire:click="view({{ $product->id }})" size="sm" class="btn-ghost">
            詳細
        </x-mary-button>
    @endif
</td>
```

#### 新規作成ボタン
```blade
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">商品管理</h2>
    @if($canEdit)
        <x-mary-button wire:click="create" class="btn-primary">
            新規商品追加
        </x-mary-button>
    @endif
</div>
```

### 5.3 フォーム無効化

#### 入力フィールドの制御
```blade
<x-mary-input 
    label="商品名" 
    wire:model="name" 
    required
    :disabled="!$canEdit"
/>

<x-mary-select 
    label="税区分" 
    wire:model.lazy="tax_type" 
    :options="$taxOptions"
    :disabled="!$canEdit"
/>
```

#### 保存ボタンの制御
```blade
@if($canEdit)
    <x-mary-button type="submit" class="btn-primary">
        @if($isEditing) 更新 @else 作成 @endif
    </x-mary-button>
@endif
```

## 6. 運用方法

### 6.1 通常時の運用
1. **データ管理**: POS端末でマスターデータを管理
2. **クラウド同期**: POS → クラウドの一方向同期
3. **管理画面**: 一般管理者は閲覧のみ

### 6.2 緊急時の運用

#### 緊急編集の実行手順
1. **状況確認**: POS障害等で緊急対応が必要
2. **SuperAdminログイン**: 緊急編集権限でログイン
3. **警告確認**: 緊急編集モード警告を確認
4. **編集実行**: 必要最小限の編集を実行
5. **POS同期**: 手動でPOS側のデータを更新

#### 注意事項
- 緊急時のみの使用に限定
- 編集後は必ずPOS側の手動同期が必要
- 変更内容をPOS管理者に共有

### 6.3 同期手順（手動）
1. **クラウド側の変更内容を確認**
2. **POS端末にアクセス**
3. **マスターデータを手動更新**
4. **動作確認**
5. **同期完了の記録**

## 7. セキュリティ考慮事項

### 7.1 権限管理
- SuperAdmin権限の厳格な管理
- 権限昇格の防止
- セッション管理の強化

### 7.2 監査ログ
```php
// 将来的な実装案
Log::info('Emergency edit executed', [
    'user_id' => auth()->id(),
    'action' => 'product_update',
    'product_id' => $product->id,
    'changes' => $product->getDirty(),
    'timestamp' => now(),
]);
```

### 7.3 アクセス制御
- IPアドレス制限（将来的な拡張）
- 二要素認証（将来的な拡張）
- 操作時間制限（将来的な拡張）

## 8. エラーメッセージ一覧

### 権限エラー
```
商品の編集権限がありません。商品マスターデータはPOS側で管理されています。緊急編集にはSuperAdmin権限が必要です。
```

```
カテゴリの編集権限がありません。カテゴリマスターデータはPOS側で管理されています。緊急編集にはSuperAdmin権限が必要です。
```

### 情報メッセージ
```
商品データはPOS側で管理されています。こちらは閲覧専用です。
編集が必要な場合は、POS端末から操作してください。
```

## 9. 今後の拡張予定

### 9.1 監査機能強化
- 詳細な操作ログ
- 変更履歴の可視化
- レポート機能

### 9.2 同期機能改善
- 半自動同期機能
- 差分確認機能
- 同期ステータス表示

### 9.3 セキュリティ強化
- 多要素認証
- 時間制限つきアクセス
- IP制限機能

---

**実装日**: 2025年8月21日  
**実装者**: Claude Code  
**レビュー**: 要  
**テスト**: Phase 2-2完了後に実施予定