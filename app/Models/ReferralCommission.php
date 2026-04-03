<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_user_id',
        'referred_user_id',
        'mobile_app_payment_id',
        'payment_amount',
        'commission_rate',
        'commission_amount',
        'currency',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function payment()
    {
        return $this->belongsTo(MobileAppPayment::class, 'mobile_app_payment_id');
    }
}
