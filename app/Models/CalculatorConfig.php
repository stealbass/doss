<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalculatorConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'calculator_type',
        'country',
        'year',
        'input_fields',
        'calculation_formula',
        'output_fields',
        'allowed_plans',
        'is_premium',
        'is_mobile_visible',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'input_fields' => 'array',
        'calculation_formula' => 'array',
        'output_fields' => 'array',
        'allowed_plans' => 'array',
        'is_premium' => 'boolean',
        'is_mobile_visible' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function logs()
    {
        return $this->hasMany(CalculatorLog::class, 'calculator_config_id');
    }

    public function getCountryInfoAttribute()
    {
        $countries = config('mobile_countries.supported_countries');
        return $countries[$this->country] ?? null;
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('calculator_type', $type);
    }

    public function scopeMobileVisible($query)
    {
        return $query->where('is_mobile_visible', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAccessibleByPlan($query, $plan)
    {
        return $query->where(function($q) use ($plan) {
            $q->where('is_premium', false)
              ->orWhereJsonContains('allowed_plans', $plan)
              ->orWhereNull('allowed_plans');
        });
    }
}
