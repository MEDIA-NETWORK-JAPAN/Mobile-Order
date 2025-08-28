<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    $adminUrl = '/'.config('app.admin_prefix');

    return response('<h1>Mobile Order System</h1><p>システムが正常に動作しています。</p><a href="'.$adminUrl.'">管理画面へ</a>');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes - Dynamic prefix for security
Route::middleware(['auth', 'verified'])->prefix(config('app.admin_prefix'))->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Products - Livewire実装（設計書準拠）
    Route::get('/products', App\Livewire\Admin\Products\ProductIndex::class)->name('products.index');
    Route::get('/products/create', App\Livewire\Admin\Products\ProductCreate::class)->name('products.create');
    Route::get('/products/{product}/edit', App\Livewire\Admin\Products\ProductEdit::class)->name('products.edit');

    // Categories
    Route::get('/categories', App\Livewire\Admin\Categories\CategoryIndex::class)->name('categories.index');
    Route::get('/categories/create', App\Livewire\Admin\Categories\CategoryCreate::class)->name('categories.create');

    // Options
    Route::get('/options', App\Livewire\Admin\Options\OptionIndex::class)->name('options.index');
    Route::get('/options/create', App\Livewire\Admin\Options\OptionCreate::class)->name('options.create');

    // Orders (Phase 3で実装予定)
    Route::get('/orders', function () {
        return response('
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>注文管理 - Mobile Order System</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-50">
            <div class="min-h-screen flex items-center justify-center">
                <div class="max-w-md mx-auto">
                    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">注文管理</h1>
                        <p class="text-gray-600 mb-6">Phase 3で実装予定の機能です</p>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-blue-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="text-left">
                                    <h3 class="text-sm font-medium text-blue-800">実装予定機能</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>注文一覧表示</li>
                                            <li>注文詳細確認</li>
                                            <li>注文ステータス管理</li>
                                            <li>POS連携機能</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="/admin" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition">
                            ダッシュボードに戻る
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ');
    })->name('orders.index');

    // Reports (Phase 4で実装予定)
    Route::get('/reports', function () {
        return response('
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>レポート - Mobile Order System</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-50">
            <div class="min-h-screen flex items-center justify-center">
                <div class="max-w-md mx-auto">
                    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">レポート機能</h1>
                        <p class="text-gray-600 mb-6">Phase 4で実装予定の機能です</p>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="text-left">
                                    <h3 class="text-sm font-medium text-green-800">実装予定機能</h3>
                                    <div class="mt-2 text-sm text-green-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>売上レポート</li>
                                            <li>商品別売上分析</li>
                                            <li>時間帯別分析</li>
                                            <li>CSVエクスポート</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="/admin" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                            ダッシュボードに戻る
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ');
    })->name('reports.index');

    // System Management (SuperAdminのみ、Phase 3で実装予定)
    Route::get('/stores', function () {
        return response('
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>店舗管理 - Mobile Order System</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-50">
            <div class="min-h-screen flex items-center justify-center">
                <div class="max-w-md mx-auto">
                    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">店舗管理</h1>
                        <p class="text-gray-600 mb-6">SuperAdmin専用機能（Phase 3で実装予定）</p>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-purple-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="text-left">
                                    <h3 class="text-sm font-medium text-purple-800">実装予定機能</h3>
                                    <div class="mt-2 text-sm text-purple-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>店舗情報管理</li>
                                            <li>店舗設定変更</li>
                                            <li>営業時間設定</li>
                                            <li>店舗別統計</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="/admin" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 transition">
                            ダッシュボードに戻る
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ');
    })->name('stores.index');

    Route::get('/users', function () {
        return response('
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>ユーザー管理 - Mobile Order System</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-50">
            <div class="min-h-screen flex items-center justify-center">
                <div class="max-w-md mx-auto">
                    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">ユーザー管理</h1>
                        <p class="text-gray-600 mb-6">SuperAdmin専用機能（Phase 3で実装予定）</p>
                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-indigo-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="text-left">
                                    <h3 class="text-sm font-medium text-indigo-800">実装予定機能</h3>
                                    <div class="mt-2 text-sm text-indigo-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>管理者アカウント管理</li>
                                            <li>権限設定変更</li>
                                            <li>店舗アサイン管理</li>
                                            <li>アカウント有効/無効</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="/admin" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            ダッシュボードに戻る
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ');
    })->name('users.index');
});

require __DIR__.'/auth.php';
