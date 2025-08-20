<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'translations',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'translations' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // リレーション
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product')
                    ->withPivot('sort_order')
                    ->withTimestamps()
                    ->orderBy('pivot_sort_order');
    }

    // スコープ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // アクセサ
    public function getTranslatedNameAttribute($language = 'ja')
    {
        if ($this->translations && isset($this->translations[$language])) {
            return $this->translations[$language];
        }
        return $this->name;
    }
}