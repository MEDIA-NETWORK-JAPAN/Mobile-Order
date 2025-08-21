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
            商品データはPOS側で管理されています。こちらは閲覧専用です。<br>
            編集が必要な場合は、POS端末から操作してください。
        </x-mary-alert>
    @endif

    {{-- ヘッダー --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">商品管理</h2>
        @if($canEdit)
            <x-mary-button wire:click="create" class="btn-primary">
                新規商品追加
            </x-mary-button>
        @endif
    </div>

    {{-- フィルター --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-mary-input 
            wire:model.live="search" 
            placeholder="商品名・コードで検索..." 
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
        
        <x-mary-select 
            wire:model.live="selectedCategory" 
            :options="$categories" 
            option-label="name" 
            option-value="id" 
            placeholder="カテゴリを選択"
        />
        
        <x-mary-select 
            wire:model.live="availabilityFilter" 
            :options="[
                ['value' => 'available', 'label' => '販売中'],
                ['value' => 'sold_out', 'label' => '売り切れ'],
                ['value' => 'not_arrived', 'label' => '未入荷'],
                ['value' => 'preparing', 'label' => '準備中']
            ]" 
            option-label="label" 
            option-value="value" 
            placeholder="在庫状態"
        />
    </div>

    {{-- テーブル --}}
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th wire:click="sortBy('code')" class="cursor-pointer">
                        商品コード
                        @if($sortField === 'code')
                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                        @endif
                    </th>
                    <th wire:click="sortBy('name')" class="cursor-pointer">
                        商品名
                        @if($sortField === 'name')
                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                        @endif
                    </th>
                    <th>カテゴリ</th>
                    <th wire:click="sortBy('price')" class="cursor-pointer">
                        価格
                        @if($sortField === 'price')
                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                        @endif
                    </th>
                    <th>税込価格</th>
                    <th>在庫状態</th>
                    <th>状態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $product->code }}</td>
                        <td>{{ $product->name }}</td>
                        <td>
                            @foreach($product->categories as $category)
                                <x-mary-badge>{{ $category->name }}</x-mary-badge>
                            @endforeach
                        </td>
                        <td>¥{{ number_format($product->price) }}</td>
                        <td>¥{{ number_format($product->tax_in_price) }}</td>
                        <td>
                            @switch($product->availability_status)
                                @case('available')
                                    <x-mary-badge type="success">販売中</x-mary-badge>
                                    @break
                                @case('sold_out')
                                    <x-mary-badge type="error">売り切れ</x-mary-badge>
                                    @break
                                @case('not_arrived')
                                    <x-mary-badge type="warning">未入荷</x-mary-badge>
                                    @break
                                @case('preparing')
                                    <x-mary-badge type="info">準備中</x-mary-badge>
                                    @break
                            @endswitch
                        </td>
                        <td>
                            @if($product->is_active)
                                <x-mary-badge type="success">有効</x-mary-badge>
                            @else
                                <x-mary-badge type="error">無効</x-mary-badge>
                            @endif
                        </td>
                        <td>
                            @if($canEdit)
                                <x-mary-button wire:click="edit({{ $product->id }})" size="sm" class="btn-ghost">
                                    編集
                                </x-mary-button>
                                <x-mary-button wire:click="delete({{ $product->id }})" size="sm" class="btn-ghost text-error">
                                    削除
                                </x-mary-button>
                            @else
                                <x-mary-button wire:click="view({{ $product->id }})" size="sm" class="btn-ghost">
                                    詳細
                                </x-mary-button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-gray-500">
                            商品が見つかりません
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ページネーション --}}
    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>