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
    <div class="flex justify-between items-center m-6">
        <h2 class="text-2xl font-bold">カテゴリ管理</h2>
        @if($canEdit)
            <button wire:click="create" class="px-4 py-2 font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                新規カテゴリ追加
            </button>
        @endif
    </div>

    {{-- フィルター --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
                wire:key="store-select-{{ $selectedStore }}"
            />
        @endif

        <x-mary-select
            wire:model.live="statusFilter"
            :options="[
                ['value' => 'active', 'label' => '有効のみ'],
                ['value' => 'inactive', 'label' => '無効のみ']
            ]"
            option-label="label"
            option-value="value"
            placeholder="状態で絞り込み"
            wire:key="status-select-{{ $statusFilter }}"
        />

        <x-mary-button 
            wire:click="clearFilters" 
            class="btn-outline"
            icon="o-x-mark"
        >
            フィルタクリア
        </x-mary-button>
    </div>

    {{-- 並び替え説明 --}}
    @if($canEdit && $categories->count() > 0)
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center text-blue-800 text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                ↑↓ボタンで表示順を変更できます
            </div>
        </div>
    @endif

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
                        <td>
                            @if($canEdit)
                                <div class="flex items-center space-x-2">
                                    <x-mary-button 
                                        wire:click="moveCategoryUp({{ $category->id }})"
                                        size="sm"
                                        class="btn-sm btn-ghost"
                                        wire:loading.attr="disabled"
                                    >
                                        ↑
                                    </x-mary-button>
                                    <span>{{ $category->sort_order }}</span>
                                    <x-mary-button 
                                        wire:click="moveCategoryDown({{ $category->id }})"
                                        size="sm" 
                                        class="btn-sm btn-ghost"
                                        wire:loading.attr="disabled"
                                    >
                                        ↓
                                    </x-mary-button>
                                </div>
                            @else
                                {{ $category->sort_order }}
                            @endif
                        </td>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->store->name }}</td>
                        <td>
                            <span class="text-gray-700">{{ $category->products_count }}件</span>
                        </td>
                        <td>
                            @if($category->is_active)
                                <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">有効</span>
                            @else
                                <span class="inline-block px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">無効</span>
                            @endif
                        </td>
                        <td>
                            @if($canEdit)
                                <x-mary-button 
                                    wire:click="editCategory({{ $category->id }})" 
                                    size="sm" 
                                    class="btn-primary btn-sm mr-2"
                                >
                                    編集
                                </x-mary-button>
                                <x-mary-button 
                                    wire:click="toggleActive({{ $category->id }})" 
                                    size="sm" 
                                    class="btn-warning btn-sm mr-2"
                                >
                                    @if($category->is_active) 無効化 @else 有効化 @endif
                                </x-mary-button>
                                <x-mary-button 
                                    wire:click="deleteCategory({{ $category->id }})" 
                                    wire:confirm="このカテゴリを削除してもよろしいですか？"
                                    size="sm" 
                                    class="btn-error btn-sm"
                                >
                                    削除
                                </x-mary-button>
                            @else
                                <x-mary-button 
                                    wire:click="editCategory({{ $category->id }})" 
                                    size="sm" 
                                    class="btn-info btn-sm"
                                >
                                    詳細
                                </x-mary-button>
                            @endif
                        </td>
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
