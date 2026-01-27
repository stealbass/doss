<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryGrid extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'year',
        'sector',
        'collective_agreement',
        'position_title',
        'category',
        'minimum_salary',
        'maximum_salary',
        'currency',
        'allowances',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'minimum_salary' => 'decimal:2',
        'maximum_salary' => 'decimal:2',
        'allowances' => 'array',
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

    public function scopeBySector($query, $sector)
    {
        return $query->where('sector', $sector);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
