<div>
    {{-- 権限別メッセージ表示 --}}
    @if($canEdit)
        <x-mary-alert type="warning" dismissible="false">
            <strong>SuperAdmin緊急編集モード</strong><br>
            カテゴリマスターデータは通常POS側で管理されています。<br>
            緊急編集後は必ずPOS側のデータを手動で同期してください。
        </x-mary-alert>
    @else
        <x-mary-alert type="info" dismissible="false">
            カテゴリデータはPOS側で管理されています。こちらは閲覧専用です。<br>
            編集が必要な場合は、POS端末から操作してください。
        </x-mary-alert>
    @endif

    {{-- ヘッダー --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">カテゴリ管理</h2>
        @if($canEdit)
            <x-mary-button wire:click="create" class="btn-primary">
                新規カテゴリ追加
            </x-mary-button>
        @endif
    </div>

    {{-- フィルター --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <x-mary-input 
            wire:model.live="search" 
            placeholder="カテゴリ名で検索..." 
            type="search"
        />
        
        @if($stores->count() > 0)
            <x-mary-select 
                wire:model.live="selectedStore" 
                :options="$stores" 
                option-label="name" 
                option-value="id" 
                placeholder="店舗を選択"
            />
        @endif
    </div>

    {{-- テーブル --}}
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th wire:click="sortBy('sort_order')" class="cursor-pointer">
                        表示順
                        @if($sortField === 'sort_order')
                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                        @endif
                    </th>
                    <th wire:click="sortBy('name')" class="cursor-pointer">
                        カテゴリ名
                        @if($sortField === 'name')
                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                        @endif
                    </th>
                    <th>店舗</th>
                    <th>商品数</th>
                    <th>状態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        @if($editingCategory === $category->id)
                            {{-- インライン編集モード --}}
                            <td>
                                <x-mary-input 
                                    wire:model="editingSortOrder" 
                                    type="number" 
                                    size="sm"
                                    class="w-20"
                                />
                            </td>
                            <td>
                                <x-mary-input 
                                    wire:model="editingName" 
                                    size="sm"
                                />
                            </td>
                            <td>{{ $category->store->name }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>
                                @if($category->is_active)
                                    <x-mary-badge type="success">有効</x-mary-badge>
                                @else
                                    <x-mary-badge type="error">無効</x-mary-badge>
                                @endif
                            </td>
                            <td>
                                <x-mary-button wire:click="updateCategory" size="sm" class="btn-success">
                                    保存
                                </x-mary-button>
                                <x-mary-button wire:click="cancelEdit" size="sm" class="btn-ghost">
                                    キャンセル
                                </x-mary-button>
                            </td>
                        @else
                            {{-- 通常表示モード --}}
                            <td>{{ $category->sort_order }}</td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->store->name }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>
                                @if($category->is_active)
                                    <x-mary-badge type="success">有効</x-mary-badge>
                                @else
                                    <x-mary-badge type="error">無効</x-mary-badge>
                                @endif
                            </td>
                            <td>
                                @if($canEdit)
                                    <x-mary-button wire:click="editCategory({{ $category->id }})" size="sm" class="btn-ghost">
                                        編集
                                    </x-mary-button>
                                    <x-mary-button wire:click="toggleActive({{ $category->id }})" size="sm" class="btn-ghost">
                                        @if($category->is_active) 無効化 @else 有効化 @endif
                                    </x-mary-button>
                                    @if($category->products_count === 0)
                                        <x-mary-button wire:click="deleteCategory({{ $category->id }})" size="sm" class="btn-ghost text-error">
                                            削除
                                        </x-mary-button>
                                    @endif
                                @else
                                    <x-mary-button wire:click="view({{ $category->id }})" size="sm" class="btn-ghost">
                                        詳細
                                    </x-mary-button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-gray-500">
                            カテゴリが見つかりません
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ページネーション --}}
    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>