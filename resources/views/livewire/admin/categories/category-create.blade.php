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
            カテゴリデータはPOS側で管理されています。作成はできません。<br>
            作成が必要な場合は、POS端末から操作してください。
        </x-mary-alert>
    @endif

    {{-- ヘッダー --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold">新規カテゴリ作成</h2>
    </div>

    {{-- フォーム --}}
    <form wire:submit.prevent="save">
        <div class="card bg-base-100 shadow-xl p-6 max-w-2xl">
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
                placeholder="例：ラーメン、サイドメニュー"
                :disabled="!$canEdit"
            />
            
            <x-mary-input 
                label="表示順" 
                wire:model="sort_order" 
                type="number" 
                required
                min="0"
                placeholder="0"
                :disabled="!$canEdit"
            />
            
            <x-mary-checkbox 
                label="有効にする" 
                wire:model="is_active"
                :disabled="!$canEdit"
            />

            {{-- ボタン --}}
            <div class="flex justify-end gap-4 mt-6">
                <x-mary-button 
                    type="button" 
                    wire:click="$dispatch('navigate', { url: '{{ route('admin.categories.index') }}' })"
                    class="btn-ghost"
                >
                    キャンセル
                </x-mary-button>
                
                @if($canEdit)
                    <x-mary-button type="submit" class="btn-primary">
                        作成
                    </x-mary-button>
                @endif
            </div>
        </div>
    </form>
</div>