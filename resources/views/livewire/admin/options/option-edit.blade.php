<div>
    {{-- 権限別メッセージ表示 --}}
    @if($canEdit)
        <x-mary-alert type="warning" dismissible="false">
            <strong>SuperAdmin緊急編集モード</strong><br>
            オプションマスターデータは通常POS側で管理されています。<br>
            緊急編集後は必ずPOS側のデータを手動で同期してください。
        </x-mary-alert>
    @else
        <x-mary-alert type="info" dismissible="false">
            オプションデータはPOS側で管理されています。編集はできません。<br>
            編集が必要な場合は、POS端末から操作してください。
        </x-mary-alert>
    @endif

    {{-- ヘッダー --}}
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold">オプション編集</h2>
        <div class="space-x-2">
            <button
                type="button"
                wire:click="backToIndex"
                class="btn btn-outline"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                オプション一覧に戻る
            </button>
            @if($canEdit)
                <x-mary-button
                    label="削除"
                    icon="c-trash"
                    wire:click="delete"
                    wire:confirm="このオプションを削除してもよろしいですか？"
                    class="btn-error"
                />
            @endif
        </div>
    </div>

    {{-- フォーム --}}
    <form wire:submit.prevent="updateOption">
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
                    label="オプション名"
                    wire:model="title"
                    type="text"
                    maxlength="45"
                    required
                    placeholder="例：麺の硬さ"
                    :disabled="!$canEdit"
                />
            </div>

            {{-- オプション設定 --}}
            <div class="card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">オプション設定</h3>

                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">必須オプション</span>
                        <input
                            type="checkbox"
                            wire:model="required"
                            class="checkbox"
                            {{ $required ? 'checked' : '' }}
                            {{ !$canEdit ? 'disabled' : '' }}
                        />
                    </label>
                </div>

                <x-mary-select
                    label="選択タイプ"
                    wire:model="selection_type"
                    :options="[
                        ['value' => 'single', 'label' => '単一選択'],
                        ['value' => 'multiple', 'label' => '複数選択']
                    ]"
                    option-label="label"
                    option-value="value"
                    required
                    :disabled="!$canEdit"
                />
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
                    オプションを更新
                    <span wire:loading wire:target="updateOption" class="loading loading-spinner loading-sm ml-2"></span>
                </button>
            @endif
        </div>

        {{-- ローディング表示 --}}
        <div wire:loading wire:target="updateOption" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
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
            <h3 class="text-lg font-semibold mb-4">選択肢設定</h3>
            
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
                    wire:click="clearFilters" 
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
                    <h4 class="font-medium mb-3">所属商品 ({{ $assignedProducts->count() }}件)</h4>
                    <div class="max-h-96 overflow-y-auto space-y-2">
                        @foreach($assignedProducts as $detail)
                            <div class="flex items-center p-2 hover:bg-gray-50 rounded">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedAssignedProducts"
                                    value="{{ $detail->id }}"
                                    class="checkbox checkbox-sm mr-3"
                                    wire:key="assigned-{{ $detail->id }}"
                                />
                                <div class="flex-1">
                                    <div class="text-sm font-medium">{{ $detail->product->code }}</div>
                                    <div class="text-xs text-gray-600">{{ $detail->product->name }}</div>
                                    @if($detail->default_selected)
                                        <span class="badge badge-outline badge-xs">デフォルト</span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-1">
                                    <button
                                        wire:click="toggleDefault({{ $detail->id }})"
                                        class="btn btn-ghost btn-xs {{ $detail->default_selected ? 'text-yellow-600' : 'text-gray-400' }}"
                                        title="{{ $detail->default_selected ? 'デフォルトを解除' : 'デフォルトに設定' }}"
                                        wire:loading.attr="disabled"
                                    >
                                        ★
                                    </button>
                                    <button
                                        wire:click="moveProductUp({{ $detail->id }})"
                                        class="btn btn-ghost btn-xs"
                                        wire:loading.attr="disabled"
                                    >
                                        ↑
                                    </button>
                                    <button
                                        wire:click="moveProductDown({{ $detail->id }})"
                                        class="btn btn-ghost btn-xs"
                                        wire:loading.attr="disabled"
                                    >
                                        ↓
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        @if($assignedProducts->count() === 0)
                            <div class="text-center text-gray-500 py-4">
                                該当する商品がありません
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- 閲覧専用の選択肢一覧 --}}
        <div class="mt-8 card bg-base-100 shadow-xl p-6">
            <h3 class="text-lg font-semibold mb-4">選択肢一覧</h3>
            <div class="space-y-2">
                @forelse($assignedProducts as $detail)
                    <div class="flex justify-between items-center p-3 border rounded">
                        <div>
                            <div class="text-sm font-medium">{{ $detail->product->code }}</div>
                            <div class="text-xs text-gray-600">{{ $detail->product->name }}</div>
                            @if($detail->default_selected)
                                <span class="badge badge-outline badge-xs">デフォルト</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500">
                            順序: {{ $detail->sort_order }}
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-4">選択肢がありません</div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- 関連商品セクション --}}
    @if($canEdit)
        <div class="mt-8 card bg-base-100 shadow-xl p-6">
            <h3 class="text-lg font-semibold mb-4">関連商品設定</h3>
            
            {{-- 検索・フィルター --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <x-mary-input
                    wire:model.live="relatedProductSearch"
                    placeholder="商品名・コードで検索..."
                    type="search"
                />
                
                <x-mary-select
                    wire:model.live="relatedProductStatusFilter"
                    :options="[
                        ['value' => 'active', 'label' => '有効のみ'],
                        ['value' => 'inactive', 'label' => '無効のみ']
                    ]"
                    option-label="label"
                    option-value="value"
                    placeholder="商品状態"
                />
                
                <x-mary-button 
                    wire:click="clearRelatedFilters" 
                    class="btn-outline"
                    icon="o-x-mark"
                >
                    クリア
                </x-mary-button>
            </div>
            
            {{-- 商品選択UI --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- 未関連商品 --}}
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium mb-3">未関連商品 ({{ $unrelatedProducts->count() }}件)</h4>
                    <div class="max-h-96 overflow-y-auto space-y-2">
                        @foreach($unrelatedProducts as $product)
                            <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedUnrelatedProducts"
                                    value="{{ $product->id }}"
                                    class="checkbox checkbox-sm mr-3"
                                    wire:key="unrelated-{{ $product->id }}"
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
                        @if($unrelatedProducts->count() === 0)
                            <div class="text-center text-gray-500 py-4">
                                該当する商品がありません
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- 移動ボタン --}}
                <div class="flex flex-col justify-center items-center space-y-4">
                    <button 
                        wire:click="linkProducts"
                        class="btn btn-primary"
                        @if(empty($selectedUnrelatedProducts)) disabled @endif
                    >
                        →<br>関連付け
                    </button>
                    
                    <button 
                        wire:click="unlinkProducts"
                        class="btn btn-secondary"
                        @if(empty($selectedRelatedProducts)) disabled @endif
                    >
                        ←<br>解除
                    </button>
                </div>
                
                {{-- 関連商品 --}}
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium mb-3">関連商品 ({{ $relatedProducts->count() }}件)</h4>
                    <div class="max-h-96 overflow-y-auto space-y-2">
                        @foreach($relatedProducts as $product)
                            <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedRelatedProducts"
                                    value="{{ $product->id }}"
                                    class="checkbox checkbox-sm mr-3"
                                    wire:key="related-{{ $product->id }}"
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
                        @if($relatedProducts->count() === 0)
                            <div class="text-center text-gray-500 py-4">
                                関連付けられた商品がありません
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- 閲覧専用の関連商品一覧 --}}
        <div class="mt-8 card bg-base-100 shadow-xl p-6">
            <h3 class="text-lg font-semibold mb-4">関連商品一覧</h3>
            <div class="space-y-2">
                @forelse($relatedProducts as $product)
                    <div class="flex justify-between items-center p-3 border rounded">
                        <div>
                            <div class="text-sm font-medium">{{ $product->code }}</div>
                            <div class="text-xs text-gray-600">{{ $product->name }}</div>
                        </div>
                        <div class="text-sm text-gray-500">
                            ¥{{ number_format($product->price) }}
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-4">関連商品がありません</div>
                @endforelse
            </div>
        </div>
    @endif
</div>