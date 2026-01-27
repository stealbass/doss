<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'duration',
        'max_users',
        'max_employee',
        'enable_chatgpt',
        'description',
        'max_advocates',
        'storage_limit',
        'trial',
        'trial_days',
        'status',
    ];

    public static $arrDuration = [
        'Lifetime' => 'Lifetime',
        'month' => 'Per Month',
        'year' => 'Per Year',
    ];

    public function getPrice($frequency)
    {
        if ($frequency === $this->duration) {
            return $this->price ?? 0;
        }
        return 0; // Or throw an exception if frequency doesn't match
    }

    public static function total_plan()
    {
        return Plan::count();
    }

    public static function most_purchese_plan()
    {
        $free_plan = Plan::where('price', '<=', 0)->orWhere('price', '>=', 0)->first()->id;
        return User::select(DB::raw('count(*) as total'))->where('type', '=', 'company')->where('plan', '!=', $free_plan)->groupBy('plan')->first();
    }
}