<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Super adminは全店舗、その他は所属店舗のみ
        $storeQuery = $user->isSuperAdmin() ? Store::query() : Store::where('id', $user->store_id);
        
        $stats = [
            'total_stores' => $user->isSuperAdmin() ? Store::count() : 1,
            'total_products' => Product::when(!$user->isSuperAdmin(), function ($query) use ($user) {
                return $query->where('store_id', $user->store_id);
            })->count(),
            'active_products' => Product::active()
                ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                    return $query->where('store_id', $user->store_id);
                })->count(),
            'today_orders' => Order::whereDate('created_at', today())
                ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                    return $query->where('store_id', $user->store_id);
                })->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}