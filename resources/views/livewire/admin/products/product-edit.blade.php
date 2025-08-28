<div>
    {{-- 権限別メッセージ表示 --}}
    @if($canEdit)
        <x-mary-alert type="warning" dismissible="false">
            <strong>SuperAdmin緊急編集モード</strong><br>
            商品マスターデータは通常POS側で管理されています。<br>
            緊急編集後は必ずPOS側のデータを手動で同期してください。
        </x-mary-alert>
    @else
        <x-mary-alert type="info" dismissible="false">
            商品データはPOS側で管理されています。編集はできません。<br>
            編集が必要な場合は、POS端末から操作してください。
        </x-mary-alert>
    @endif

    {{-- ヘッダー --}}
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold">商品編集</h2>
        <div class="space-x-2">
            <button
                type="button"
                wire:click="backToIndex"
                class="btn btn-outline"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                商品一覧に戻る
            </button>
            @if($canEdit)
                <x-mary-button
                    label="削除"
                    icon="c-trash"
                    wire:click="delete"
                    wire:confirm="この商品を削除してもよろしいですか？"
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
                    label="商品コード"
                    wire:model="code"
                    required
                    placeholder="例：PROD001"
                    :disabled="!$canEdit"
                />

                <x-mary-input
                    label="商品名"
                    wire:model="name"
                    required
                    placeholder="例：醤油ラーメン"
                    :disabled="!$canEdit"
                />

                <x-mary-textarea
                    label="商品説明"
                    wire:model="description"
                    rows="3"
                    placeholder="商品の詳細説明"
                    :disabled="!$canEdit"
                />
            </div>

            {{-- 価格・税設定 --}}
            <div class="card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">価格・税設定</h3>

                <x-mary-input
                    label="価格（税抜）"
                    wire:model.lazy="price"
                    type="number"
                    required
                    placeholder="1000"
                    :disabled="!$canEdit"
                />

                <x-mary-input
                    label="税込価格"
                    value="{{ $taxInPrice ? '¥' . number_format($taxInPrice) : '自動計算' }}"
                    readonly
                    disabled
                />

                <x-mary-input
                    label="原価"
                    wire:model="cost"
                    type="number"
                    placeholder="500"
                    :disabled="!$canEdit"
                />

                <x-mary-select
                    label="税区分"
                    wire:model.lazy="tax_type"
                    :options="[
                        ['value' => 'standard', 'label' => '標準税率'],
                        ['value' => 'reduced', 'label' => '軽減税率'],
                        ['value' => 'exempt', 'label' => '非課税'],
                        ['value' => 'non_taxable', 'label' => '不課税']
                    ]"
                    option-label="label"
                    option-value="value"
                    required
                    :disabled="!$canEdit"
                />
            </div>

            {{-- 在庫・表示設定 --}}
            <div class="card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">在庫・表示設定</h3>

                <x-mary-select
                    label="在庫状態"
                    wire:model="availability_status"
                    :options="[
                        ['value' => 'available', 'label' => '販売中'],
                        ['value' => 'sold_out', 'label' => '売り切れ'],
                        ['value' => 'not_arrived', 'label' => '未入荷'],
                        ['value' => 'preparing', 'label' => '準備中']
                    ]"
                    option-label="label"
                    option-value="value"
                    required
                    :disabled="!$canEdit"
                />

                <x-mary-input
                    label="在庫メッセージ"
                    wire:model="availability_message"
                    placeholder="例：本日分は売り切れました"
                    :disabled="!$canEdit"
                />

                <x-mary-input
                    label="提供予定時刻"
                    wire:model="expected_available_time"
                    type="time"
                    :disabled="!$canEdit"
                />

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

            {{-- カテゴリ --}}
            <div class="card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">カテゴリ</h3>

                {{-- カテゴリ選択 --}}
                @if($categories->count() > 0)
                    <div class="space-y-2 max-h-32 overflow-y-auto border rounded p-2">
                        @foreach($categories as $category)
                            <label class="label cursor-pointer">
                                <span class="label-text">{{ $category->name }}</span>
                                <input
                                    type="checkbox"
                                    wire:model="selectedCategories"
                                    value="{{ $category->id }}"
                                    class="checkbox checkbox-sm"
                                    {{ !$canEdit ? 'disabled' : '' }}
                                />
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">カテゴリが設定されていません</p>
                @endif
            </div>

            {{-- 商品画像 --}}
            <div class="card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">商品画像</h3>

                {{-- 画像プレビュー --}}
                @if($image_url)
                    <div class="mb-4">
                        <img
                            src="{{ $image_url }}"
                            alt="{{ $product->name }}"
                            class="w-full max-w-xs h-48 object-cover rounded-lg shadow-md mx-auto"
                            onerror="this.src='https://via.placeholder.com/300x200?text=画像なし'; this.onerror=null;"
                        />
                    </div>
                @else
                    <div class="mb-4 flex justify-center">
                        <div class="w-full max-w-xs h-48 bg-gray-200 rounded-lg flex items-center justify-center">
                            <span class="text-gray-500">画像なし</span>
                        </div>
                    </div>
                @endif

                <x-mary-input
                    label="画像URL"
                    wire:model.live="image_url"
                    type="url"
                    placeholder="https://example.com/image.jpg"
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
                    商品を更新
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
</div>
