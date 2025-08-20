<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'product_id',
        'filename',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // リレーション
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // スコープ
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // アクセサ
    public function getUrlAttribute()
    {
        return asset('storage/images/' . $this->filename);
    }
}