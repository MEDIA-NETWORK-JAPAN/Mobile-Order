<x-layouts.app>
    <x-slot:title>{{ $title ?? '管理画面' }}</x-slot:title>
    
    {{-- デスクトップナビゲーション --}}
    <x-slot:desktopNav>
        <a href="{{ route('admin.dashboard') }}" 
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            ダッシュボード
        </a>
        <a href="{{ route('admin.products.index') }}" 
           class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
            商品管理
        </a>
        <a href="{{ route('admin.categories.index') }}" 
           class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
            カテゴリ管理
        </a>
        <a href="{{ route('admin.options.index') }}" 
           class="nav-link {{ request()->routeIs('admin.options.*') ? 'active' : '' }}">
            オプション管理
        </a>
        <a href="{{ route('admin.orders.index') }}" 
           class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
            注文管理
        </a>
    </x-slot:desktopNav>

    {{-- サイドバー（デスクトップ用） --}}
    <x-slot:sidebar>
        <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 md:pt-16 md:bg-gray-50 md:border-r md:border-gray-200">
            <div class="flex-1 flex flex-col min-h-0 pt-5 pb-4 overflow-y-auto">
                <nav class="mt-5 flex-1 px-2 space-y-1">
                    {{-- ダッシュボード --}}
                    <a href="{{ route('admin.dashboard') }}" 
                       class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <x-mary-icon name="o-home" class="mr-3 h-6 w-6" />
                        ダッシュボード
                    </a>

                    {{-- 商品管理 --}}
                    <div class="space-y-1">
                        <div class="sidebar-section-title">
                            <x-mary-icon name="o-cube" class="mr-3 h-5 w-5" />
                            商品管理
                        </div>
                        <a href="{{ route('admin.products.index') }}" 
                           class="sidebar-sublink {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                            商品一覧
                        </a>
                        <a href="{{ route('admin.categories.index') }}" 
                           class="sidebar-sublink {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                            カテゴリ管理
                        </a>
                        <a href="{{ route('admin.options.index') }}" 
                           class="sidebar-sublink {{ request()->routeIs('admin.options.*') ? 'active' : '' }}">
                            オプション管理
                        </a>
                    </div>

                    {{-- 注文管理 --}}
                    <div class="space-y-1">
                        <div class="sidebar-section-title">
                            <x-mary-icon name="o-clipboard-document-list" class="mr-3 h-5 w-5" />
                            注文管理
                        </div>
                        <a href="{{ route('admin.orders.index') }}" 
                           class="sidebar-sublink {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            注文一覧
                        </a>
                    </div>

                    {{-- システム管理 --}}
                    @if(auth()->user()->isSuperAdmin())
                        <div class="space-y-1">
                            <div class="sidebar-section-title">
                                <x-mary-icon name="o-cog-6-tooth" class="mr-3 h-5 w-5" />
                                システム管理
                            </div>
                            <a href="{{ route('admin.stores.index') }}" 
                               class="sidebar-sublink {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}">
                                店舗管理
                            </a>
                            <a href="{{ route('admin.users.index') }}" 
                               class="sidebar-sublink {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                ユーザー管理
                            </a>
                        </div>
                    @endif
                </nav>
            </div>
        </aside>
    </x-slot:sidebar>

    {{-- モバイルサイドバー --}}
    <div x-data="{ mobileSidebarOpen: false }" 
         @keydown.escape="mobileSidebarOpen = false">
        {{-- オーバーレイ --}}
        <div x-show="mobileSidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="md:hidden fixed inset-0 z-40 bg-gray-600 bg-opacity-75"
             @click="mobileSidebarOpen = false"
             style="display: none;"></div>

        {{-- モバイルサイドバー --}}
        <div x-show="mobileSidebarOpen"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="md:hidden fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200"
             style="display: none;">
            
            <div class="flex items-center justify-between flex-shrink-0 px-4 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">メニュー</h2>
                <button @click="mobileSidebarOpen = false"
                        class="-mr-2 flex items-center justify-center h-10 w-10 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500">
                    <x-mary-icon name="o-x-mark" class="h-6 w-6" />
                </button>
            </div>
            
            <nav class="mt-5 flex-1 px-2 space-y-1 overflow-y-auto">
                {{-- モバイル用のナビゲーション（サイドバーと同じ構造） --}}
                <a href="{{ route('admin.dashboard') }}" 
                   class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                   @click="mobileSidebarOpen = false">
                    <x-mary-icon name="o-home" class="mr-3 h-6 w-6" />
                    ダッシュボード
                </a>

                <div class="space-y-1">
                    <div class="sidebar-section-title">
                        <x-mary-icon name="o-cube" class="mr-3 h-5 w-5" />
                        商品管理
                    </div>
                    <a href="{{ route('admin.products.index') }}" 
                       class="sidebar-sublink {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
                       @click="mobileSidebarOpen = false">
                        商品一覧
                    </a>
                    <a href="{{ route('admin.categories.index') }}" 
                       class="sidebar-sublink {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                       @click="mobileSidebarOpen = false">
                        カテゴリ管理
                    </a>
                    <a href="{{ route('admin.options.index') }}" 
                       class="sidebar-sublink {{ request()->routeIs('admin.options.*') ? 'active' : '' }}"
                       @click="mobileSidebarOpen = false">
                        オプション管理
                    </a>
                </div>

                <div class="space-y-1">
                    <div class="sidebar-section-title">
                        <x-mary-icon name="o-clipboard-document-list" class="mr-3 h-5 w-5" />
                        注文管理
                    </div>
                    <a href="{{ route('admin.orders.index') }}" 
                       class="sidebar-sublink {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
                       @click="mobileSidebarOpen = false">
                        注文一覧
                    </a>
                </div>

                @if(auth()->user()->isSuperAdmin())
                    <div class="space-y-1">
                        <div class="sidebar-section-title">
                            <x-mary-icon name="o-cog-6-tooth" class="mr-3 h-5 w-5" />
                            システム管理
                        </div>
                        <a href="{{ route('admin.stores.index') }}" 
                           class="sidebar-sublink {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}"
                           @click="mobileSidebarOpen = false">
                            店舗管理
                        </a>
                        <a href="{{ route('admin.users.index') }}" 
                           class="sidebar-sublink {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                           @click="mobileSidebarOpen = false">
                            ユーザー管理
                        </a>
                    </div>
                @endif
            </nav>
        </div>
    </div>

    {{-- メインコンテンツ --}}
    <div class="md:pl-64">
        {{ $slot }}
    </div>
</x-layouts.app>