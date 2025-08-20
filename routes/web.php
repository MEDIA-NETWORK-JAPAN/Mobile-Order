<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

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
    return view('welcome');
});

// Admin routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::get('/products', App\Livewire\Admin\Products\ProductIndex::class)->name('products.index');
    Route::get('/products/create', App\Livewire\Admin\Products\ProductForm::class)->name('products.create');
    Route::get('/products/{product}/edit', App\Livewire\Admin\Products\ProductForm::class)->name('products.edit');
    
    // Categories
    Route::get('/categories', App\Livewire\Admin\Categories\CategoryIndex::class)->name('categories.index');
    Route::get('/categories/create', App\Livewire\Admin\Categories\CategoryCreate::class)->name('categories.create');
});

require __DIR__.'/auth.php';
