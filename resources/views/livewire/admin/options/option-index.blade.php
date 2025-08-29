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
            オプションデータはPOS側で管理されています。こちらは閲覧専用です。<br>
            編集が必要な場合は、POS端末から操作してください。
        </x-mary-alert>
    @endif

    {{-- ヘッダー --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">オプション管理</h2>
        @if($canEdit)
            <a href="{{ route('admin.options.create') }}" wire:navigate>
                <button class="px-4 py-2 font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    新規オプション追加
                </button>
            </a>
        @endif
    </div>

    {{-- フィルター --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-mary-input 
            wire:model.live="search" 
            placeholder="オプション名で検索..." 
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
            wire:model.live="requiredFilter"
            :options="[
                ['value' => 'required', 'label' => '必須のみ'],
                ['value' => 'optional', 'label' => '任意のみ']
            ]"
            option-label="label"
            option-value="value"
            placeholder="必須/任意で絞り込み"
            wire:key="required-select-{{ $requiredFilter }}"
        />

        <x-mary-button 
            wire:click="clearFilters" 
            class="btn-outline"
            icon="o-x-mark"
        >
            フィルタクリア
        </x-mary-button>
    </div>

    {{-- テーブル --}}
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th wire:click="sortBy('title')" class="cursor-pointer">
                        オプション名
                        @if($sortField === 'title')
                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                        @endif
                    </th>
                    <th>店舗</th>
                    <th>選択肢数</th>
                    <th>必須</th>
                    <th>選択タイプ</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($options as $option)
                    <tr>
                        <td>{{ $option->title }}</td>
                        <td>{{ $option->store->name }}</td>
                        <td>{{ $option->option_details_count }}</td>
                        <td>
                            @if($option->required)
                                <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">必須</span>
                            @else
                                <span class="inline-block px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">任意</span>
                            @endif
                        </td>
                        <td>
                            <span class="inline-block px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                {{ $option->selection_type === 'single' ? '単一選択' : '複数選択' }}
                            </span>
                        </td>
                        <td>
                            @if($canEdit)
                                <x-mary-button 
                                    wire:click="editOption({{ $option->id }})" 
                                    size="sm" 
                                    class="btn-primary btn-sm mr-2"
                                >
                                    編集
                                </x-mary-button>
                                <x-mary-button 
                                    wire:click="deleteOption({{ $option->id }})"
                                    wire:confirm="本当にこのオプションを削除しますか？"
                                    size="sm" 
                                    class="btn-error btn-sm"
                                >
                                    削除
                                </x-mary-button>
                            @else
                                <x-mary-button 
                                    wire:click="editOption({{ $option->id }})" 
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
                            オプションが見つかりません
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ページネーション --}}
    <div class="mt-4">
        {{ $options->links() }}
    </div>
</div>