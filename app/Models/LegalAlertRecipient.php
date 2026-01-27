<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalAlertRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_alert_id',
        'user_id',
        'email_status',
        'whatsapp_status',
        'push_status',
        'viewed_at',
        'clicked_at',
        'dismissed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'clicked_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function alert()
    {
        return $this->belongsTo(LegalAlert::class, 'legal_alert_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
