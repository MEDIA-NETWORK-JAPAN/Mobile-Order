<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'title',
        'required',
        'selection_type',
        'translations',
    ];

    protected $casts = [
        'required' => 'boolean',
        'translations' => 'array',
    ];

    // リレーション
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_to_options')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('pivot_sort_order');
    }

    public function optionDetails()
    {
        return $this->hasMany(OptionDetail::class)->orderBy('sort_order');
    }

    // スコープ
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }

    public function scopeSingle($query)
    {
        return $query->where('selection_type', 'single');
    }

    public function scopeMultiple($query)
    {
        return $query->where('selection_type', 'multiple');
    }

    // アクセサ
    public function getTranslatedTitleAttribute($language = 'ja')
    {
        if ($this->translations && isset($this->translations[$language])) {
            return $this->translations[$language];
        }

        return $this->title;
    }
}
