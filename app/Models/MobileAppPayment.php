<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileAppPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mobile_app_subscription_id',
        'mobile_app_plan_id',
        'transaction_id',
        'flutterwave_reference',
        'payment_method',
        'amount',
        'currency',
        'fees',
        'status',
        'paid_at',
        'failed_at',
        'refunded_at',
        'flutterwave_data',
        'error_message',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'flutterwave_data' => 'array',
    ];

    /**
     * Relation: Appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: Appartient à un abonnement
     */
    public function subscription()
    {
        return $this->belongsTo(MobileAppSubscription::class, 'mobile_app_subscription_id');
    }

    /**
     * Relation: Appartient à un plan
     */
    public function plan()
    {
        return $this->belongsTo(MobileAppPlan::class, 'mobile_app_plan_id');
    }

    /**
     * Scope: Paiements réussis
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }

    /**
     * Scope: Paiements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Paiements échoués
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Marque le paiement comme réussi
     */
    public function markAsSuccessful($data = [])
    {
        $this->update([
            'status' => 'successful',
            'paid_at' => now(),
            'flutterwave_data' => $data,
        ]);
    }

    /**
     * Marque le paiement comme échoué
     */
    public function markAsFailed($errorMessage = null, $data = [])
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'flutterwave_data' => $data,
        ]);
    }

    /**
     * Calcule le montant net (après frais)
     */
    public function getNetAmountAttribute()
    {
        return $this->amount - $this->fees;
    }

    /**
     * Vérifie si le paiement a réussi
     */
    public function isSuccessful()
    {
        return $this->status === 'successful';
    }
}
