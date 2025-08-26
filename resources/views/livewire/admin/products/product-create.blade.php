<div x-data="{}" @alert.window="alert($event.detail.message)">
    {{-- 権限別メッセージ表示 --}}
    @if($canEdit)
        <x-mary-alert type="warning" dismissible="false">
            <strong>SuperAdmin緊急作成モード</strong><br>
            商品マスターデータは通常POS側で管理されています。<br>
            緊急作成後は必ずPOS側のデータを手動で同期してください。
        </x-mary-alert>
    @else
        <x-mary-alert type="info" dismissible="false">
            商品データはPOS側で管理されています。作成はできません。<br>
            作成が必要な場合は、POS端末から操作してください。
        </x-mary-alert>
    @endif

    {{-- ヘッダー --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold">新規商品追加</h2>
    </div>

    {{-- フォーム --}}
    <form wire:submit.prevent="save" onsubmit="console.log('Form submitted!')">
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
                    label="説明" 
                    wire:model="description" 
                    rows="3"
                    placeholder="商品の説明を入力"
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
                    required
                    min="0"
                    placeholder="0"
                    :disabled="!$canEdit"
                />
                
                <x-mary-checkbox 
                    label="有効" 
                    wire:model="is_active"
                    :disabled="!$canEdit"
                />
            </div>

            {{-- カテゴリ --}}
            <div class="card bg-base-100 shadow-xl p-6">
                <h3 class="text-lg font-semibold mb-4">カテゴリ</h3>
                
                <div class="space-y-2">
                    @foreach($categories as $category)
                        <x-mary-checkbox 
                            wire:model="selectedCategories" 
                            value="{{ $category->id }}"
                            :disabled="!$canEdit"
                        >
                            {{ $category->name }}
                        </x-mary-checkbox>
                    @endforeach
                </div>
            </div>

            {{-- 画像アップロード（一時的に無効化） --}}
            <div class="card bg-base-100 shadow-xl p-6 md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">商品画像</h3>
                
                @if($canEdit)
                    <div class="alert alert-info">
                        <span>画像アップロード機能は現在メンテナンス中です</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ボタン --}}
        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('admin.products.index') }}" wire:navigate>
                <x-mary-button type="button" class="btn-ghost">
                    キャンセル
                </x-mary-button>
            </a>
            
            @if($canEdit)
                <x-mary-button wire:click="testMethod" type="button" class="btn-secondary mr-2">
                    テスト
                </x-mary-button>
                <x-mary-button wire:click="save" type="button" class="btn-primary">
                    作成
                </x-mary-button>
            @endif
        </div>
    </form>
</div>