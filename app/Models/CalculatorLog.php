<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalculatorLog extends Model
{
    use HasFactory;

    // Activer explicitement les timestamps Laravel
    public $timestamps = true;

    protected $fillable = [
        'calculator_config_id',
        'user_id',
        'input_data',
        'output_data',
        'country',
        'user_plan',
        'session_id',
        'calculated_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function calculator()
    {
        return $this->belongsTo(CalculatorConfig::class, 'calculator_config_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
