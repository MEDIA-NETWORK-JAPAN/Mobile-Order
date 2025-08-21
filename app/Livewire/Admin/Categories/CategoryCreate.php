<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use App\Models\Store;
use Livewire\Component;
use Mary\Traits\Toast;

class CategoryCreate extends Component
{
    use Toast;

    public $store_id = '';
    public $name = '';
    public $sort_order = 0;
    public $is_active = true;

    protected $rules = [
        'store_id' => 'required|exists:stores,id',
        'name' => 'required|max:255',
        'sort_order' => 'required|integer|min:0',
        'is_active' => 'required|boolean',
    ];

    public function mount()
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $this->store_id = $user->store_id;
        }
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();
        
        // SuperAdmin権限チェック（緊急編集機能）
        if (!$user->isSuperAdmin()) {
            $this->error('カテゴリの編集権限がありません。カテゴリマスターデータはPOS側で管理されています。緊急編集にはSuperAdmin権限が必要です。');
            return;
        }
        
        // 権限チェック
        if (!$user->hasStoreAccess($this->store_id)) {
            $this->error('この店舗にカテゴリを追加する権限がありません。');
            return;
        }

        Category::create([
            'store_id' => $this->store_id,
            'name' => $this->name,
            'sort_order' => (int)$this->sort_order,
            'is_active' => $this->is_active,
        ]);

        $this->success('カテゴリを作成しました。');

        return redirect()->route('admin.categories.index');
    }

    public function render()
    {
        $user = auth()->user();
        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect([$user->store]);

        return view('livewire.admin.categories.category-create', [
            'stores' => $stores,
            'canEdit' => $user->isSuperAdmin(), // 編集権限フラグ
        ]);
    }
}