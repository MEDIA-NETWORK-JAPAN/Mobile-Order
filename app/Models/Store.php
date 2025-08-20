<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'phone',
        'email',
        'address',
        'business_hours',
        'qr_mode',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // リレーション
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function systemSettings()
    {
        return $this->hasMany(SystemSetting::class);
    }

    public function posHealthCheck()
    {
        return $this->hasOne(PosHealthCheck::class);
    }

    // スコープ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}