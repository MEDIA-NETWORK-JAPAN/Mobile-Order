<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionDetail extends Model
{
    use HasFactory;

    protected $table = 'option_detail';

    protected $fillable = [
        'option_id',
        'product_id',
        'default_selected',
        'sort_order',
    ];

    protected $casts = [
        'default_selected' => 'boolean',
        'sort_order' => 'integer',
    ];

    // リレーション
    public function option()
    {
        return $this->belongsTo(Option::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // スコープ
    public function scopeDefault($query)
    {
        return $query->where('default_selected', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
