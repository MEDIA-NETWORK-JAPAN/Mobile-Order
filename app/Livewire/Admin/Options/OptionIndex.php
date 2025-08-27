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

    public $sortField = 'title';

    public $sortDirection = 'asc';

    // 権限制御
    public $canEdit = false;

    // インライン編集
    public $editingOption = null;

    public $editingTitle = '';

    public function mount()
    {
        $user = auth()->user();

        // SuperAdminのみ編集可能（POS中心設計）
        $this->canEdit = $user->isSuperAdmin();

        if (! $user->isSuperAdmin() && $user->store_id) {
            $this->selectedStore = $user->store_id;
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

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function editOption($optionId)
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
            $this->error('オプションの編集権限がありません。オプションマスターデータはPOS側で管理されています。');

            return;
        }

        if (! $user->hasStoreAccess($option->store_id)) {
            $this->error('このオプションを編集する権限がありません。');

            return;
        }

        $this->editingOption = $optionId;
        $this->editingTitle = $option->title;
    }

    public function updateOption()
    {
        $this->validate([
            'editingTitle' => 'required|max:45',
        ]);

        $option = Option::find($this->editingOption);

        if (! $option) {
            $this->error('オプションが見つかりません。');

            return;
        }

        $option->update([
            'title' => $this->editingTitle,
        ]);

        $this->success('オプションを更新しました。');
        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->editingOption = null;
        $this->editingTitle = '';
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
            ->withCount(['optionDetails', 'products'])
            ->when($this->search, fn ($query) => $query->where('title', 'like', "%{$this->search}%")
            )
            ->when($this->selectedStore, fn ($query) => $query->where('store_id', $this->selectedStore)
            )
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