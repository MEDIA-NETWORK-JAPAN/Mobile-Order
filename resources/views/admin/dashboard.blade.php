<x-layouts.admin>
    <x-slot:title>ダッシュボード</x-slot:title>
    
    <x-slot:header>
        <div class="section-header">
            <h1 class="section-title">ダッシュボード</h1>
            <p class="section-description">Mobile Order System管理画面</p>
        </div>
    </x-slot:header>

    <div class="space-y-6">
        {{-- 統計カード --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- 今日の注文数 --}}
            <div class="card-container">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-mary-icon name="o-shopping-cart" class="h-8 w-8 text-blue-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">今日の注文数</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $todayOrders ?? 0 }}</div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    <x-mary-icon name="o-arrow-trending-up" class="h-3 w-3 mr-0.5" />
                                    {{ $orderGrowth ?? '+0%' }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- 今日の売上 --}}
            <div class="card-container">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-mary-icon name="o-banknotes" class="h-8 w-8 text-green-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">今日の売上</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">¥{{ number_format($todaySales ?? 0) }}</div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    <x-mary-icon name="o-arrow-trending-up" class="h-3 w-3 mr-0.5" />
                                    {{ $salesGrowth ?? '+0%' }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- アクティブ商品数 --}}
            <div class="card-container">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-mary-icon name="o-cube" class="h-8 w-8 text-amber-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">アクティブ商品数</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $activeProducts ?? 0 }}</div>
                                <div class="ml-2 text-sm text-gray-500">/ {{ $totalProducts ?? 0 }}</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- 在庫切れ商品 --}}
            <div class="card-container">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-mary-icon name="o-exclamation-triangle" class="h-8 w-8 text-red-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">在庫切れ商品</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $soldOutProducts ?? 0 }}</div>
                                @if(($soldOutProducts ?? 0) > 0)
                                    <div class="ml-2 text-sm text-red-600">要確認</div>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- クイックアクション --}}
        <div class="card-container">
            <div class="card-header">
                <h2 class="card-title">クイックアクション</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('admin.products.index') }}" 
                   class="flex flex-col items-center p-4 text-center rounded-lg border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-colors">
                    <x-mary-icon name="o-cube" class="h-8 w-8 text-primary-600 mb-2" />
                    <span class="text-sm font-medium text-gray-900">商品管理</span>
                    <span class="text-xs text-gray-500">商品の追加・編集</span>
                </a>
                
                <a href="{{ route('admin.categories.index') }}" 
                   class="flex flex-col items-center p-4 text-center rounded-lg border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-colors">
                    <x-mary-icon name="o-tag" class="h-8 w-8 text-primary-600 mb-2" />
                    <span class="text-sm font-medium text-gray-900">カテゴリ管理</span>
                    <span class="text-xs text-gray-500">カテゴリの追加・編集</span>
                </a>
                
                <div class="flex flex-col items-center p-4 text-center rounded-lg border border-gray-200 opacity-50">
                    <x-mary-icon name="o-clipboard-document-list" class="h-8 w-8 text-gray-400 mb-2" />
                    <span class="text-sm font-medium text-gray-900">注文管理</span>
                    <span class="text-xs text-gray-500">Phase 3で実装予定</span>
                </div>
                
                <div class="flex flex-col items-center p-4 text-center rounded-lg border border-gray-200 opacity-50">
                    <x-mary-icon name="o-chart-bar" class="h-8 w-8 text-gray-400 mb-2" />
                    <span class="text-sm font-medium text-gray-900">レポート</span>
                    <span class="text-xs text-gray-500">Phase 4で実装予定</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- 最新の注文 --}}
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title">最新の注文</h2>
                </div>
                @if(isset($recentOrders) && $recentOrders->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentOrders as $order)
                            <div class="list-item">
                                <div class="list-item-content">
                                    <div class="flex-1">
                                        <div class="list-item-title">注文 #{{ $order->id }}</div>
                                        <div class="list-item-description">
                                            {{ $order->created_at->format('H:i') }} - ¥{{ number_format($order->total_amount) }}
                                        </div>
                                    </div>
                                    <div class="status-badge status-badge-{{ $order->status_color }}">
                                        {{ $order->status_label }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        <a href="#" class="btn-ghost text-primary-600">
                            すべての注文を見る
                        </a>
                    </div>
                @else
                    <div class="empty-state">
                        <x-mary-icon name="o-clipboard-document-list" class="empty-state-icon" />
                        <h3 class="empty-state-title">注文がありません</h3>
                        <p class="empty-state-description">新しい注文が入ると、ここに表示されます。</p>
                    </div>
                @endif
            </div>

            {{-- 人気商品 --}}
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title">人気商品（今週）</h2>
                </div>
                @if(isset($popularProducts) && $popularProducts->count() > 0)
                    <div class="space-y-4">
                        @foreach($popularProducts as $product)
                            <div class="list-item">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <img class="h-10 w-10 rounded-lg object-cover" 
                                             src="{{ $product->image_url ?? '/images/placeholder-product.png' }}" 
                                             alt="{{ $product->name }}">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="list-item-title truncate">{{ $product->name }}</div>
                                        <div class="list-item-description">
                                            {{ $product->orders_count }}回注文
                                        </div>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">
                                        ¥{{ number_format($product->price) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <x-mary-icon name="o-chart-bar" class="empty-state-icon" />
                        <h3 class="empty-state-title">データがありません</h3>
                        <p class="empty-state-description">注文データが蓄積されると、人気商品が表示されます。</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- システム状態（SuperAdminのみ） --}}
        @if(auth()->user()->isSuperAdmin())
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title">システム状態</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-2 w-2 bg-green-400 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">データベース</div>
                            <div class="text-xs text-gray-500">正常</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-2 w-2 bg-{{ $posStatus === 'online' ? 'green' : 'red' }}-400 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">POS連携</div>
                            <div class="text-xs text-gray-500">{{ $posStatus === 'online' ? '接続中' : '切断' }}</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-2 w-2 bg-green-400 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">キャッシュ</div>
                            <div class="text-xs text-gray-500">動作中</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>