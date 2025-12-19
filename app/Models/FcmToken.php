<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les tokens FCM (Firebase Cloud Messaging)
 * 
 * Stocke les tokens de notification push pour chaque utilisateur
 * Un utilisateur peut avoir plusieurs tokens (plusieurs appareils)
 */
class FcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform', // 'android' ou 'ios'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
