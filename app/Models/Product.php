<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'code',
        'name',
        'description',
        'price',
        'tax_in_price',
        'cost',
        'tax_type',
        'availability_status',
        'availability_message',
        'expected_available_time',
        'translations',
        'image_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'tax_in_price' => 'integer',
        'cost' => 'integer',
        'translations' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'expected_available_time' => 'datetime:H:i',
    ];

    // リレーション
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('pivot_sort_order');
    }

    public function options()
    {
        return $this->belongsToMany(Option::class, 'product_to_options')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('pivot_sort_order');
    }

    public function optionDetails()
    {
        return $this->hasMany(OptionDetail::class);
    }

    // TODO: Phase 2後半で実装 - 画像管理
    // public function images()
    // {
    //     return $this->hasMany(Image::class)->orderBy('sort_order');
    // }

    // TODO: Phase 3で実装 - 注文機能
    // public function orderItems()
    // {
    //     return $this->hasMany(OrderItem::class);
    // }

    // public function cartLogs()
    // {
    //     return $this->hasMany(CartLog::class);
    // }

    // スコープ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('availability_status', 'available');
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // アクセサ
    public function getTranslatedNameAttribute($language = 'ja')
    {
        if ($this->translations && isset($this->translations[$language]['name'])) {
            return $this->translations[$language]['name'];
        }

        return $this->name;
    }

    public function getTranslatedDescriptionAttribute($language = 'ja')
    {
        if ($this->translations && isset($this->translations[$language]['description'])) {
            return $this->translations[$language]['description'];
        }

        return $this->description;
    }
}
