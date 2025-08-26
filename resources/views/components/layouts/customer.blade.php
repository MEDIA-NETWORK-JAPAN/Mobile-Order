<x-layouts.app>
    <x-slot:title>{{ $title ?? 'Mobile Order' }}</x-slot:title>
    
    {{-- ヘッダーアクション（カートアイコンなど） --}}
    <x-slot:headerActions>
        {{-- カートアイコン（今は静的実装） --}}
        <button class="relative flex items-center text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <x-mary-icon name="o-shopping-cart" class="h-6 w-6" />
            {{-- カート数バッジ（将来のLivewire実装時に動的化） --}}
            <span class="absolute -top-2 -right-2 h-5 w-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">0</span>
        </button>
        
        {{-- 言語切替 --}}
        <x-mary-dropdown>
            <x-slot:trigger>
                <button class="flex items-center text-sm text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <x-mary-icon name="o-language" class="h-6 w-6" />
                </button>
            </x-slot:trigger>
            
            <x-mary-menu-item title="日本語" />
            <x-mary-menu-item title="English" />
            <x-mary-menu-item title="中文(简体)" />
            <x-mary-menu-item title="中文(繁體)" />
            <x-mary-menu-item title="한국어" />
        </x-mary-dropdown>
    </x-slot:headerActions>

    {{-- メインコンテンツ（フルスクリーン） --}}
    <div class="pb-16 md:pb-0">
        {{ $slot }}
    </div>

    {{-- モバイルボトムナビゲーション --}}
    <x-slot:mobileNav>
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-30">
            <div class="grid grid-cols-4 py-2">
                {{-- メニュー --}}
                <a href="{{ route('customer.menu') }}" 
                   class="nav-item {{ request()->routeIs('customer.menu') ? 'active' : '' }}">
                    <x-mary-icon name="o-squares-2x2" class="w-6 h-6 mx-auto" />
                    <span class="text-xs mt-1 block text-center">メニュー</span>
                </a>
                
                {{-- カート --}}
                <button class="nav-item relative">
                    <x-mary-icon name="o-shopping-cart" class="w-6 h-6 mx-auto" />
                    <span class="text-xs mt-1 block text-center">カート</span>
                    {{-- カート数バッジ（将来のLivewire実装時に動的化） --}}
                    <span class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">0</span>
                </button>
                
                {{-- 注文履歴 --}}
                <a href="{{ route('customer.orders') }}" 
                   class="nav-item {{ request()->routeIs('customer.orders') ? 'active' : '' }}">
                    <x-mary-icon name="o-clock" class="w-6 h-6 mx-auto" />
                    <span class="text-xs mt-1 block text-center">注文履歴</span>
                </a>
                
                {{-- ヘルプ --}}
                <a href="{{ route('customer.help') }}" 
                   class="nav-item {{ request()->routeIs('customer.help') ? 'active' : '' }}">
                    <x-mary-icon name="o-question-mark-circle" class="w-6 h-6 mx-auto" />
                    <span class="text-xs mt-1 block text-center">ヘルプ</span>
                </a>
            </div>
        </nav>
    </x-slot:mobileNav>
</x-layouts.app>