<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'type',
        'target_audience',
        'target_plan',
        'custom_data',
        'image_url',
        'action_url',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'successful_sends',
        'failed_sends',
        'opened_count',
        'clicked_count',
        'created_by',
    ];

    protected $casts = [
        'custom_data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Relation: Créateur de la notification
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Notifications envoyées
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope: Notifications planifiées
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
                     ->where('scheduled_at', '>', now());
    }

    /**
     * Scope: Brouillons
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Vérifie si la notification peut être envoyée
     */
    public function canBeSent()
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    /**
     * Calcule le taux d'ouverture
     */
    public function getOpenRateAttribute()
    {
        if ($this->successful_sends === 0) {
            return 0;
        }
        return round(($this->opened_count / $this->successful_sends) * 100, 2);
    }

    /**
     * Calcule le taux de clic
     */
    public function getClickRateAttribute()
    {
        if ($this->opened_count === 0) {
            return 0;
        }
        return round(($this->clicked_count / $this->opened_count) * 100, 2);
    }

    /**
     * Obtient la couleur du badge selon le statut
     */
    public function getStatusColorAttribute()
    {
        return [
            'draft' => 'secondary',
            'scheduled' => 'info',
            'sending' => 'warning',
            'sent' => 'success',
            'failed' => 'danger',
        ][$this->status] ?? 'secondary';
    }

    /**
     * Obtient la couleur du type
     */
    public function getTypeColorAttribute()
    {
        return [
            'general' => 'primary',
            'promotion' => 'success',
            'alert' => 'danger',
            'update' => 'info',
        ][$this->type] ?? 'primary';
    }
}
