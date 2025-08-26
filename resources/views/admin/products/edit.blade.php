<x-layouts.admin>
    <x-slot:title>商品編集 - 管理画面</x-slot:title>

    <div class="max-w-4xl mx-auto">
        {{-- 権限別メッセージ表示 --}}
        @if($user->isSuperAdmin())
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-yellow-800">
                            <strong>SuperAdmin緊急編集モード</strong><br>
                            商品マスターデータは通常POS側で管理されています。<br>
                            緊急編集後は必ずPOS側のデータを手動で同期してください。
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-blue-800">
                            商品データはPOS側で管理されています。編集はできません。<br>
                            編集が必要な場合は、POS端末から操作してください。
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ヘッダー --}}
        <div class="mb-6">
            <h2 class="text-2xl font-bold">商品編集: {{ $product->name }}</h2>
        </div>

        {{-- エラーメッセージ --}}
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">エラーが発生しました</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($user->isSuperAdmin())
        {{-- フォーム --}}
        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- 基本情報 --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">基本情報</h3>
                    
                    @if($stores->count() > 1)
                        <div class="mb-4">
                            <label for="store_id" class="block text-sm font-medium text-gray-700 mb-2">店舗 *</label>
                            <select name="store_id" id="store_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">選択してください</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ (old('store_id', $product->store_id) == $store->id) ? 'selected' : '' }}>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="store_id" value="{{ $product->store_id }}">
                    @endif
                    
                    <div class="mb-4">
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">商品コード *</label>
                        <input type="text" name="code" id="code" value="{{ old('code', $product->code) }}" required 
                               placeholder="例：PROD001" maxlength="45"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">商品名 *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required 
                               placeholder="例：醤油ラーメン" maxlength="255"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">説明</label>
                        <textarea name="description" id="description" rows="3" maxlength="1000"
                                  placeholder="商品の説明を入力"
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>

                {{-- 価格・税設定 --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">価格・税設定</h3>
                    
                    <div class="mb-4">
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">価格（税抜） *</label>
                        <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" required 
                               placeholder="1000" min="-999999" max="999999"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="tax_type" class="block text-sm font-medium text-gray-700 mb-2">税区分 *</label>
                        <select name="tax_type" id="tax_type" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="standard" {{ (old('tax_type', $product->tax_type) == 'standard') ? 'selected' : '' }}>標準税率</option>
                            <option value="reduced" {{ (old('tax_type', $product->tax_type) == 'reduced') ? 'selected' : '' }}>軽減税率</option>
                            <option value="exempt" {{ (old('tax_type', $product->tax_type) == 'exempt') ? 'selected' : '' }}>非課税</option>
                            <option value="non_taxable" {{ (old('tax_type', $product->tax_type) == 'non_taxable') ? 'selected' : '' }}>不課税</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="cost" class="block text-sm font-medium text-gray-700 mb-2">原価</label>
                        <input type="number" name="cost" id="cost" value="{{ old('cost', $product->cost) }}" 
                               placeholder="500" min="0"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- 在庫・表示設定 --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">在庫・表示設定</h3>
                    
                    <div class="mb-4">
                        <label for="availability_status" class="block text-sm font-medium text-gray-700 mb-2">在庫状態 *</label>
                        <select name="availability_status" id="availability_status" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="available" {{ (old('availability_status', $product->availability_status) == 'available') ? 'selected' : '' }}>販売中</option>
                            <option value="sold_out" {{ (old('availability_status', $product->availability_status) == 'sold_out') ? 'selected' : '' }}>売り切れ</option>
                            <option value="not_arrived" {{ (old('availability_status', $product->availability_status) == 'not_arrived') ? 'selected' : '' }}>未入荷</option>
                            <option value="preparing" {{ (old('availability_status', $product->availability_status) == 'preparing') ? 'selected' : '' }}>準備中</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="availability_message" class="block text-sm font-medium text-gray-700 mb-2">在庫メッセージ</label>
                        <input type="text" name="availability_message" id="availability_message" value="{{ old('availability_message', $product->availability_message) }}" 
                               placeholder="例：本日分は売り切れました" maxlength="255"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="expected_available_time" class="block text-sm font-medium text-gray-700 mb-2">提供予定時刻</label>
                        <input type="time" name="expected_available_time" id="expected_available_time" value="{{ old('expected_available_time', $product->expected_available_time) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">表示順 *</label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $product->sort_order) }}" required 
                               placeholder="0" min="0"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">有効</span>
                        </label>
                    </div>
                </div>

                {{-- カテゴリ --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">カテゴリ</h3>
                    
                    <div class="space-y-2">
                        @foreach($categories as $category)
                            <label class="flex items-center">
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                                       {{ in_array($category->id, old('categories', $product->categories->pluck('id')->toArray())) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ボタン --}}
            <div class="flex justify-end gap-4 mt-6">
                <a href="{{ route('admin.products.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    キャンセル
                </a>
                
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    更新
                </button>
            </div>
        </form>
        @else
        {{-- 閲覧専用表示 --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- 基本情報 --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">基本情報</h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">店舗</label>
                    <div class="text-sm text-gray-900">{{ $product->store->name }}</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">商品コード</label>
                    <div class="text-sm text-gray-900">{{ $product->code }}</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">商品名</label>
                    <div class="text-sm text-gray-900">{{ $product->name }}</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">説明</label>
                    <div class="text-sm text-gray-900">{{ $product->description ?: '説明なし' }}</div>
                </div>
            </div>

            {{-- 価格・税設定 --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">価格・税設定</h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">価格（税抜）</label>
                    <div class="text-sm text-gray-900">¥{{ number_format($product->price) }}</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">税込価格</label>
                    <div class="text-sm text-gray-900">¥{{ number_format($product->tax_in_price) }}</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">税区分</label>
                    <div class="text-sm text-gray-900">
                        @switch($product->tax_type)
                            @case('standard')標準税率@break
                            @case('reduced')軽減税率@break
                            @case('exempt')非課税@break
                            @case('non_taxable')不課税@break
                        @endswitch
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">原価</label>
                    <div class="text-sm text-gray-900">{{ $product->cost ? '¥' . number_format($product->cost) : '未設定' }}</div>
                </div>
            </div>

            {{-- 在庫・表示設定 --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">在庫・表示設定</h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">在庫状態</label>
                    <div class="text-sm text-gray-900">
                        @switch($product->availability_status)
                            @case('available')販売中@break
                            @case('sold_out')売り切れ@break
                            @case('not_arrived')未入荷@break
                            @case('preparing')準備中@break
                        @endswitch
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">在庫メッセージ</label>
                    <div class="text-sm text-gray-900">{{ $product->availability_message ?: 'メッセージなし' }}</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">提供予定時刻</label>
                    <div class="text-sm text-gray-900">{{ $product->expected_available_time ?: '未設定' }}</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">表示順</label>
                    <div class="text-sm text-gray-900">{{ $product->sort_order }}</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">状態</label>
                    <div class="text-sm text-gray-900">{{ $product->is_active ? '有効' : '無効' }}</div>
                </div>
            </div>

            {{-- カテゴリ --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">カテゴリ</h3>
                
                <div class="space-y-2">
                    @forelse($product->categories as $category)
                        <div class="text-sm text-gray-900">{{ $category->name }}</div>
                    @empty
                        <div class="text-sm text-gray-500">カテゴリなし</div>
                    @endforelse
                </div>
            </div>
        </div>
        
        <div class="flex justify-end mt-6">
            <a href="{{ route('admin.products.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                戻る
            </a>
        </div>
        @endif
    </div>
</x-layouts.admin>