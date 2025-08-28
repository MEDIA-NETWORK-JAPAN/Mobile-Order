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
    <div class="flex justify-between items-center m-6">
        <div>
            <h2 class="text-2xl font-bold">商品管理</h2>
            @if($selectedCategory)
                @php
                    $category = App\Models\Category::find($selectedCategory);
                @endphp
                @if($category)
                    <p class="text-sm text-gray-600 mt-1">
                        カテゴリ「{{ $category->name }}」でフィルター中
                        <button wire:click="$set('selectedCategory', '')" class="ml-2 text-blue-600 hover:text-blue-800 underline">
                            クリア
                        </button>
                    </p>
                @endif
            @endif
        </div>
        @if($canEdit)
            <a href="{{ route('admin.products.create') }}">
                <button class="px-4 py-2 font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    新規商品追加
                </button>
            </a>
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
                wire:key="store-select-{{ $selectedStore }}"
            />
        @endif

        <x-mary-select
            wire:model.live="selectedCategory"
            :options="$categories"
            option-label="name"
            option-value="id"
            placeholder="カテゴリを選択"
            wire:key="category-select-{{ $selectedCategory }}"
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
            wire:key="availability-select-{{ $availabilityFilter }}"
        />
    </div>

    {{-- フィルタクリアボタン --}}
    <div class="mb-6 flex justify-end">
        <button 
            wire:click="clearFilters" 
            class="btn btn-outline btn-sm"
            {{ (!$search && !$selectedCategory && !$availabilityFilter && (!auth()->user()->isSuperAdmin() || !$selectedStore)) ? 'disabled' : '' }}
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            フィルタをクリア
        </button>
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
                                <span class="badge badge-outline badge-primary">{{ $category->name }}</span>
                            @endforeach
                        </td>
                        <td>¥{{ number_format($product->price) }}</td>
                        <td>¥{{ number_format($product->tax_in_price) }}</td>
                        <td>
                            @switch($product->availability_status)
                                @case('available')
                                    <span class="badge badge-success">販売中</span>
                                    @break
                                @case('sold_out')
                                    <span class="badge badge-error">売り切れ</span>
                                    @break
                                @case('not_arrived')
                                    <span class="badge badge-warning">未入荷</span>
                                    @break
                                @case('preparing')
                                    <span class="badge badge-info">準備中</span>
                                    @break
                            @endswitch
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="badge badge-success">有効</span>
                            @else
                                <span class="badge badge-error">無効</span>
                            @endif
                        </td>
                        <td>
                            @if($canEdit)
                                <a href="{{ route('admin.products.edit', $product) }}">
                                    <button class="px-3 py-1 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors mr-2">
                                        編集
                                    </button>
                                </a>
                                <button wire:click="deleteProduct({{ $product->id }})"
                                        wire:confirm="本当にこの商品を削除しますか？"
                                        wire:key="delete-btn-{{ $product->id }}"
                                        class="px-3 py-1 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors">
                                    削除
                                </button>
                            @else
                                <a href="{{ route('admin.products.edit', $product) }}">
                                    <button class="px-3 py-1 text-sm font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-md transition-colors">
                                        詳細
                                    </button>
                                </a>
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
