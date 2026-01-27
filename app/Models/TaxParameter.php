<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'year',
        'tax_type',
        'parameter_name',
        'min_value',
        'max_value',
        'rate',
        'flat_amount',
        'currency',
        'description',
        'calculation_formula',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'rate' => 'decimal:4',
        'flat_amount' => 'decimal:2',
        'calculation_formula' => 'array',
        'is_active' => 'boolean',
    ];

    public function getCountryInfoAttribute()
    {
        $countries = config('mobile_countries.supported_countries');
        return $countries[$this->country] ?? null;
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('tax_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
