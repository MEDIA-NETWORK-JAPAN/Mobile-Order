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
            カテゴリデータはPOS側で管理されています。編集はできません。<br>
            編集が必要な場合は、POS端末から操作してください。
        </x-mary-alert>
    @endif

    {{-- ヘッダー --}}
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold">カテゴリ編集</h2>
        <div class="space-x-2">
            <button
                type="button"
                wire:click="backToIndex"
                class="btn btn-outline"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                カテゴリ一覧に戻る
            </button>
            @if($canEdit)
                <x-mary-button
                    label="削除"
                    icon="c-trash"
                    wire:click="delete"
                    wire:confirm="このカテゴリを削除してもよろしいですか？"
                    class="btn-error"
                />
            @endif
        </div>
    </div>

    {{-- フォーム --}}
    <form wire:submit.prevent="update">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- 基本情報 --}}
            <div class="card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">基本情報</h3>

                @if($stores->count() > 1)
                    <x-mary-select
                        label="店舗"
                        wire:model="store_id"
                        :options="$stores"
                        option-label="name"
                        option-value="id"
                        required
                        :disabled="!$canEdit"
                    />
                @endif

                <x-mary-input
                    label="カテゴリ名"
                    wire:model="name"
                    required
                    placeholder="例：ラーメン"
                    :disabled="!$canEdit"
                />

                <x-mary-textarea
                    label="カテゴリ説明"
                    wire:model="description"
                    rows="3"
                    placeholder="カテゴリの詳細説明"
                    :disabled="!$canEdit"
                />
            </div>

            {{-- 表示設定 --}}
            <div class="card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">表示設定</h3>

                <x-mary-input
                    label="表示順"
                    wire:model="sort_order"
                    type="number"
                    min="0"
                    required
                    :disabled="!$canEdit"
                />

                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">有効</span>
                        <input
                            type="checkbox"
                            wire:model="is_active"
                            class="checkbox"
                            {{ $is_active ? 'checked' : '' }}
                            {{ !$canEdit ? 'disabled' : '' }}
                        />
                    </label>
                </div>
            </div>
        </div>

        {{-- ボタン --}}
        <div class="mt-8 flex justify-center space-x-4">
            <button
                type="button"
                wire:click="backToIndex"
                class="btn btn-outline btn-lg"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                キャンセル
            </button>

            @if($canEdit)
                <button
                    type="submit"
                    class="btn btn-primary btn-lg"
                    wire:loading.attr="disabled"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    カテゴリを更新
                    <span wire:loading wire:target="update" class="loading loading-spinner loading-sm ml-2"></span>
                </button>
            @endif
        </div>

        {{-- ローディング表示 --}}
        <div wire:loading wire:target="update" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-base-100 p-6 rounded-lg shadow-xl">
                <div class="flex items-center space-x-3">
                    <span class="loading loading-spinner loading-lg"></span>
                    <span class="text-lg">更新中...</span>
                </div>
            </div>
        </div>
    </form>

    {{-- 商品選択セクション --}}
    @if($canEdit)
        <div id="products-section" class="mt-8 card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">商品設定</h3>
                
                {{-- 検索・フィルター --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <x-mary-input
                        wire:model.live="productSearch"
                        placeholder="商品名・コードで検索..."
                        type="search"
                    />
                    
                    <x-mary-select
                        wire:model.live="productStatusFilter"
                        :options="[
                            ['value' => 'active', 'label' => '有効のみ'],
                            ['value' => 'inactive', 'label' => '無効のみ']
                        ]"
                        option-label="label"
                        option-value="value"
                        placeholder="商品状態"
                    />
                    
                    <x-mary-button 
                        wire:click="clearProductSearch" 
                        class="btn-outline"
                        icon="o-x-mark"
                    >
                        クリア
                    </x-mary-button>
                </div>
                
                {{-- 商品選択UI --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- 未所属商品 --}}
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-3">未所属商品 ({{ $unassignedProducts->count() }}件)</h4>
                        <div class="max-h-96 overflow-y-auto space-y-2">
                            @foreach($unassignedProducts as $product)
                                <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedUnassignedProducts"
                                        value="{{ $product->id }}"
                                        class="checkbox checkbox-sm mr-3"
                                        wire:key="unassigned-{{ $product->id }}"
                                    />
                                    <div class="flex-1">
                                        <div class="text-sm font-medium">{{ $product->code }}</div>
                                        <div class="text-xs text-gray-600">{{ $product->name }}</div>
                                        @if(!$product->is_active)
                                            <span class="badge badge-outline badge-xs">無効</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                            @if($unassignedProducts->count() === 0)
                                <div class="text-center text-gray-500 py-4">
                                    該当する商品がありません
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- 移動ボタン --}}
                    <div class="flex flex-col justify-center items-center space-y-4">
                        <button 
                            wire:click="assignProducts"
                            class="btn btn-primary"
                            @if(empty($selectedUnassignedProducts)) disabled @endif
                        >
                            →<br>追加
                        </button>
                        
                        <button 
                            wire:click="unassignProducts"
                            class="btn btn-secondary"
                            @if(empty($selectedAssignedProducts)) disabled @endif
                        >
                            ←<br>削除
                        </button>
                    </div>
                    
                    {{-- 所属商品 --}}
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-3">
                            所属商品 ({{ $assignedProducts->count() }}件)
                            @if($assignedProducts->count() > 0)
                                <span class="text-xs text-gray-500 ml-2">↑↓で並び替え</span>
                            @endif
                        </h4>
                        <div class="max-h-96 overflow-y-auto space-y-2">
                            @foreach($assignedProducts as $product)
                                <div class="flex items-center p-2 hover:bg-gray-50 rounded">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedAssignedProducts"
                                        value="{{ $product->id }}"
                                        class="checkbox checkbox-sm mr-3"
                                        wire:key="assigned-{{ $product->id }}"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-sm font-medium">{{ $product->code }}</div>
                                                <div class="text-xs text-gray-600">{{ $product->name }}</div>
                                                @if(!$product->is_active)
                                                    <span class="badge badge-outline badge-xs">無効</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <button 
                                                    wire:click="moveProductUp({{ $product->id }})"
                                                    class="btn btn-xs btn-ghost"
                                                    wire:loading.attr="disabled"
                                                    title="上に移動"
                                                >
                                                    ↑
                                                </button>
                                                <button 
                                                    wire:click="moveProductDown({{ $product->id }})"
                                                    class="btn btn-xs btn-ghost"
                                                    wire:loading.attr="disabled"
                                                    title="下に移動"
                                                >
                                                    ↓
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @if($assignedProducts->count() === 0)
                                <div class="text-center text-gray-500 py-4">
                                    関連付けられた商品がありません
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
</div>