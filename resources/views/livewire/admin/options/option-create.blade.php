<div>
    {{-- 権限別メッセージ表示 --}}
    @if($canEdit)
        <x-mary-alert type="warning" dismissible="false">
            <strong>SuperAdmin緊急作成モード</strong><br>
            オプションマスターデータは通常POS側で管理されています。<br>
            緊急作成後は必ずPOS側のデータを手動で同期してください。
        </x-mary-alert>
    @else
        <x-mary-alert type="info" dismissible="false">
            オプションデータはPOS側で管理されています。作成はできません。<br>
            作成が必要な場合は、POS端末から操作してください。
        </x-mary-alert>
    @endif

    {{-- ヘッダー --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold">新規オプション追加</h2>
    </div>

    {{-- フォーム --}}
    <form wire:submit.prevent="save">
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
                    required
                    placeholder="例：麺の硬さ、トッピング"
                    maxlength="45"
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

        {{-- 説明 --}}
        <div class="card bg-base-100 shadow-xl p-6 mt-6">
            <h3 class="text-lg font-semibold mb-4">オプション作成について</h3>
            <div class="text-sm text-gray-600 space-y-2">
                <p>• オプション作成後は、「オプション詳細（選択肢）」の追加が必要です</p>
                <p>• 例：「麺の硬さ」オプションに「やわらかめ」「ふつう」「かため」を追加</p>
                <p>• 商品とオプションの関連付けは商品編集画面で行います</p>
            </div>
        </div>

        {{-- ボタン --}}
        <div class="mt-8 flex justify-center space-x-4">
            <button
                type="button"
                wire:click="$dispatch('navigate', { url: '{{ route('admin.options.index') }}' })"
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
                    オプションを作成
                    <span wire:loading wire:target="save" class="loading loading-spinner loading-sm ml-2"></span>
                </button>
            @endif
        </div>

        {{-- ローディング表示 --}}
        <div wire:loading wire:target="save" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-base-100 p-6 rounded-lg shadow-xl">
                <div class="flex items-center space-x-3">
                    <span class="loading loading-spinner loading-lg"></span>
                    <span class="text-lg">作成中...</span>
                </div>
            </div>
        </div>
    </form>
</div>