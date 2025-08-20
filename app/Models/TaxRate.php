<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_type',
        'rate',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    // Scopes
    public function scopeByType($query, $taxType)
    {
        return $query->where('tax_type', $taxType);
    }

    // Helper methods
    public static function getRate($taxType)
    {
        $taxRate = static::where('tax_type', $taxType)->first();
        return $taxRate ? $taxRate->rate : 0;
    }

    public static function calculateTaxAmount($amount, $taxType)
    {
        $rate = static::getRate($taxType);
        return round($amount * ($rate / 100));
    }

    public static function calculateTaxIncludedAmount($amount, $taxType)
    {
        $taxAmount = static::calculateTaxAmount($amount, $taxType);
        return $amount + $taxAmount;
    }
}