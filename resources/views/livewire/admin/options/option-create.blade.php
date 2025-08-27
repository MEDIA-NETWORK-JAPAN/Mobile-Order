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

            {{-- 設定 --}}
            <div class="card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">設定</h3>
                
                <x-mary-checkbox 
                    label="必須オプション" 
                    wire:model="required"
                    hint="チェックすると、このオプションが必須選択になります"
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
        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('admin.options.index') }}" wire:navigate>
                <button type="button" class="px-4 py-2 font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                    キャンセル
                </button>
            </a>
            
            @if($canEdit)
                <button type="submit" class="px-4 py-2 font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    作成
                </button>
            @endif
        </div>
    </form>
</div>