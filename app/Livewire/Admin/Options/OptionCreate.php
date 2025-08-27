<?php

namespace App\Livewire\Admin\Options;

use App\Models\Option;
use App\Models\Store;
use Livewire\Component;
use Mary\Traits\Toast;

class OptionCreate extends Component
{
    use Toast;

    // Form fields
    public $store_id = '';

    public $title = '';

    public $required = false;

    protected $rules = [
        'store_id' => 'required|exists:stores,id',
        'title' => 'required|max:45',
        'required' => 'required|boolean',
    ];

    public function mount()
    {
        $user = auth()->user();

        \Log::info('OptionCreate mount() called', ['user_role' => $user->role]);

        if (! $user->isSuperAdmin()) {
            $this->store_id = $user->store_id;
        } else {
            // SuperAdminの場合は最初の店舗をデフォルトに設定
            $firstStore = \App\Models\Store::active()->first();
            if ($firstStore) {
                $this->store_id = $firstStore->id;
                \Log::info('OptionCreate: store_id set to', ['store_id' => $this->store_id]);
            }
        }
    }

    public function save()
    {
        \Log::info('=== OptionCreate save() method START ===');

        $user = auth()->user();

        // デバッグ用ログ出力
        \Log::info('OptionCreate save() called', [
            'user_role' => $user->role,
            'user_id' => $user->id,
            'store_id' => $this->store_id,
            'title' => $this->title,
            'required' => $this->required,
        ]);

        // SuperAdmin権限チェック（緊急編集機能）
        if (! $user->isSuperAdmin()) {
            \Log::warning('OptionCreate: Non-SuperAdmin attempted to create option', ['user_id' => $user->id]);
            $this->addError('permission', 'オプションの作成権限がありません。オプションマスターデータはPOS側で管理されています。緊急作成にはSuperAdmin権限が必要です。');

            return;
        }

        try {
            $this->validate();
            \Log::info('OptionCreate: Validation passed');
        } catch (\Exception $e) {
            \Log::error('OptionCreate: Validation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        $option = Option::create([
            'store_id' => $this->store_id,
            'title' => $this->title,
            'required' => $this->required,
            'selection_type' => 'single', // デフォルトは単一選択
            'translations' => null,
        ]);

        \Log::info('OptionCreate: Option created successfully', ['option_id' => $option->id]);

        $this->success('オプションを作成しました。');

        return $this->redirectRoute('admin.options.index');
    }

    public function render()
    {
        $user = auth()->user();

        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect([$user->store]);

        return view('livewire.admin.options.option-create', [
            'stores' => $stores,
            'canEdit' => $user->isSuperAdmin(),
        ])->layout('components.layouts.admin', ['title' => 'オプション作成 - 管理画面']);
    }
}