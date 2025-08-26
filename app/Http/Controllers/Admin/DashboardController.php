<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
// use App\Models\Order; // TODO: Phase 3で実装
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

        // ダッシュボード統計データ（Phase 3実装まではダミーデータ）
        $todayOrders = 15; // ダミーデータ
        $yesterdayOrders = 12; // ダミーデータ
        $todaySales = 45600; // ダミーデータ
        $yesterdaySales = 38900; // ダミーデータ

        $activeProducts = Product::where('is_active', true)
            ->where('availability_status', 'available')
            ->when(! $user->isSuperAdmin(), function ($query) use ($user) {
                return $query->where('store_id', $user->store_id);
            })->count();

        $totalProducts = Product::when(! $user->isSuperAdmin(), function ($query) use ($user) {
            return $query->where('store_id', $user->store_id);
        })->count();

        $soldOutProducts = Product::where('availability_status', 'sold_out')
            ->when(! $user->isSuperAdmin(), function ($query) use ($user) {
                return $query->where('store_id', $user->store_id);
            })->count();

        // 成長率計算
        $orderGrowth = $yesterdayOrders > 0
            ? '+'.round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1).'%'
            : ($todayOrders > 0 ? '+100%' : '0%');

        $salesGrowth = $yesterdaySales > 0
            ? '+'.round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1).'%'
            : ($todaySales > 0 ? '+100%' : '0%');

        // 最新の注文（5件） - Phase 3実装まではダミーデータ
        $recentOrders = collect([
            (object) [
                'id' => 1,
                'status' => 'pending',
                'status_label' => '待機中',
                'status_color' => 'warning',
                'total_amount' => 1200,
                'created_at' => now()->subMinutes(5),
            ],
            (object) [
                'id' => 2,
                'status' => 'preparing',
                'status_label' => '準備中',
                'status_color' => 'info',
                'total_amount' => 800,
                'created_at' => now()->subMinutes(15),
            ],
        ]);

        // 人気商品（今週、注文回数順） - Phase 3実装まではダミーデータ
        $popularProducts = Product::when(! $user->isSuperAdmin(), function ($query) use ($user) {
            return $query->where('store_id', $user->store_id);
        })
            ->limit(5)
            ->get()
            ->map(function ($product) {
                $product->image_url = $product->image_url; // 既存のimage_urlフィールドを使用
                $product->orders_count = rand(5, 20); // ダミーデータ

                return $product;
            });

        // POS接続状態（仮実装）
        $posStatus = 'online'; // 実際はPOSヘルスチェックテーブルから取得

        return view('admin.dashboard', compact(
            'todayOrders', 'yesterdayOrders', 'todaySales', 'yesterdaySales',
            'activeProducts', 'totalProducts', 'soldOutProducts',
            'orderGrowth', 'salesGrowth',
            'recentOrders', 'popularProducts', 'posStatus'
        ));
    }
}
