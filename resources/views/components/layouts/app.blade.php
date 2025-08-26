<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Mobile Order System') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans antialiased">
    {{-- アプリケーション全体のレイアウト --}}
    <div class="min-h-screen flex flex-col">
        {{-- ヘッダー（モバイル・タブレット・デスクトップ対応） --}}
        <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    {{-- ロゴエリア --}}
                    <div class="flex items-center">
                        {{-- モバイル メニューボタン（管理画面用） --}}
                        <button type="button" 
                                class="md:hidden -ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
                                @click="mobileSidebarOpen = true">
                            <x-mary-icon name="o-bars-3" class="h-6 w-6" />
                        </button>
                        
                        {{-- ロゴ --}}
                        <div class="flex-shrink-0 flex items-center ml-4 md:ml-0">
                            <a href="{{ route('dashboard') }}" class="text-xl font-bold text-primary-600">
                                Mobile Order
                            </a>
                        </div>
                    </div>

                    {{-- デスクトップナビゲーション --}}
                    <nav class="hidden md:flex space-x-8">
                        {{ $desktopNav ?? '' }}
                    </nav>

                    {{-- ヘッダー右側アクション --}}
                    <div class="flex items-center space-x-4">
                        {{ $headerActions ?? '' }}
                        
                        {{-- ユーザーメニュー --}}
                        @auth
                            <x-mary-dropdown>
                                <x-slot:trigger>
                                    <button class="flex items-center text-sm rounded-full text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                        <x-mary-icon name="o-user-circle" class="h-8 w-8" />
                                    </button>
                                </x-slot:trigger>
                                
                                <x-mary-menu-item title="プロフィール" link="{{ route('profile.edit') }}" />
                                <x-mary-menu-separator />
                                <x-mary-menu-item title="ログアウト" link="{{ route('logout') }}" 
                                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();" />
                                
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                            </x-mary-dropdown>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        {{-- メインコンテンツエリア --}}
        <div class="flex-1 flex">
            {{-- サイドバー（デスクトップ用） --}}
            {{ $sidebar ?? '' }}
            
            {{-- メインコンテンツ --}}
            <main class="flex-1 relative overflow-y-auto focus:outline-none">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        {{-- ページヘッダー --}}
                        @if(isset($header))
                            <div class="mb-6">
                                {{ $header }}
                            </div>
                        @endif
                        
                        {{-- メインコンテンツ --}}
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>

        {{-- フッター（モバイルナビゲーション） --}}
        {{ $mobileNav ?? '' }}
    </div>

    {{-- Toast通知 --}}
    <x-mary-toast />

    @livewireScripts
</body>
</html>