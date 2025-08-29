<?php

namespace App\Livewire\Admin\Options;

use App\Models\Option;
use App\Models\Store;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class OptionIndex extends Component
{
    use Toast, WithPagination;

    public $search = '';

    public $selectedStore = '';

    public $requiredFilter = '';

    public $sortField = 'title';

    public $sortDirection = 'asc';

    // 権限制御
    public $canEdit = false;

    public function mount()
    {
        $user = auth()->user();

        // SuperAdminのみ編集可能（POS中心設計）
        $this->canEdit = $user->isSuperAdmin();

        // URLパラメータまたはユーザーの店舗IDから店舗フィルタを設定
        if (request()->has('store')) {
            $this->selectedStore = request()->get('store');
        } elseif (! $user->isSuperAdmin() && $user->store_id) {
            $this->selectedStore = $user->store_id;
        }

        // セッションフラッシュメッセージをToast形式で表示
        if (session('success')) {
            $this->success(session('success'));
        }

        if (session('error')) {
            $this->error(session('error'));
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedStore()
    {
        $this->resetPage();
    }

    public function updatedRequiredFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        // 権限に応じて保持すべき値を記憶
        $user = auth()->user();
        
        // クエリパラメータを構築
        $params = [];
        if (!$user->isSuperAdmin() && $user->store_id) {
            $params['store'] = $user->store_id;
        }
        
        // ページリダイレクトでフィルタクリア
        return redirect()->route('admin.options.index', $params);
    }

    public function editOption($optionId)
    {
        return redirect()->route('admin.options.edit', $optionId);
    }


    public function deleteOption($optionId)
    {
        $option = Option::find($optionId);

        if (! $option) {
            $this->error('オプションが見つかりません。');

            return;
        }

        // 権限チェック
        $user = auth()->user();

        // SuperAdmin権限チェック（緊急編集機能）
        if (! $user->isSuperAdmin()) {
            $this->error('オプションの削除権限がありません。オプションマスターデータはPOS側で管理されています。');

            return;
        }

        if (! $user->hasStoreAccess($option->store_id)) {
            $this->error('このオプションを削除する権限がありません。');

            return;
        }

        // 関連商品があるかチェック
        if ($option->products()->count() > 0) {
            $this->error('商品が関連付けられているオプションは削除できません。');

            return;
        }

        $option->delete();
        $this->success('オプションを削除しました。');
    }

    public function render()
    {
        $user = auth()->user();

        // オプションクエリ
        $optionsQuery = Option::with(['store'])
            ->withCount(['optionDetails'])
            ->when($this->search, fn ($query) => $query->where('title', 'like', "%{$this->search}%")
            )
            ->when($this->selectedStore, fn ($query) => $query->where('store_id', $this->selectedStore)
            )
            ->when($this->requiredFilter === 'required', fn ($query) => $query->where('required', true))
            ->when($this->requiredFilter === 'optional', fn ($query) => $query->where('required', false))
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('store_id', $user->store_id)
            )
            ->orderBy($this->sortField, $this->sortDirection);

        $options = $optionsQuery->paginate(10);

        // フィルター用データ
        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect();

        return view('livewire.admin.options.option-index', [
            'options' => $options,
            'stores' => $stores,
            'canEdit' => $user->isSuperAdmin(), // 編集権限フラグ
        ])->layout('components.layouts.admin', ['title' => 'オプション管理 - 管理画面']);
    }
}